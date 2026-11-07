<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * The only place stock levels change.
 *
 * Stock is kept per colour when a product has colours (the product's stock_quantity is then
 * the sum of its colours), otherwise per product. Every change:
 *   - runs in a database transaction,
 *   - locks the affected rows first (SELECT … FOR UPDATE) so two checkouts can't sell the same unit,
 *   - writes an InventoryMovement row (who, when, why, how many, stock afterwards, unit cost).
 */
class InventoryService
{
    /**
     * Add or remove stock by hand (restock, count correction, damage, write-off).
     * $change is signed: +5 adds five, -2 removes two.
     */
    public function adjust(Product $product, ?ProductVariant $variant, int $change, string $type, ?string $note = null, ?User $by = null, ?float $unitCost = null): InventoryMovement
    {
        if (! array_key_exists($type, InventoryMovement::TYPES)) {
            throw new InvalidArgumentException("Unknown stock movement type [$type].");
        }
        if ($change === 0) {
            throw new InvalidArgumentException('A stock adjustment needs a quantity.');
        }
        if (in_array($type, ['damage', 'write_off'], true) && $change > 0) {
            throw new InvalidArgumentException('Damage and write-offs remove stock.');
        }

        return DB::transaction(function () use ($product, $variant, $change, $type, $note, $by, $unitCost) {
            [$product, $variant] = $this->lock($product, $variant);

            return $this->apply($product, $variant, $change, $type, [
                'note' => $note,
                'user_id' => $by?->id,
                'unit_cost' => $unitCost,
            ]);
        });
    }

    /**
     * Set the stock to an exact count (e.g. after a stock take or an Excel import).
     * Returns null when the count was already right.
     */
    public function setStock(Product $product, ?ProductVariant $variant, int $quantity, string $type = 'adjustment', ?string $note = null, ?User $by = null): ?InventoryMovement
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Stock cannot be negative.');
        }

        return DB::transaction(function () use ($product, $variant, $quantity, $type, $note, $by) {
            [$product, $variant] = $this->lock($product, $variant);
            $current = $variant ? $variant->stock_quantity : $product->stock_quantity;

            if ($quantity === $current) {
                return null;
            }

            return $this->apply($product, $variant, $quantity - $current, $type, ['note' => $note, 'user_id' => $by?->id]);
        });
    }

    /**
     * Take the order's items out of stock. Call inside the checkout transaction, after
     * the order items exist. Throws InsufficientStockException (rolling back the order)
     * when anything has sold out in the meantime.
     *
     * With $allowShortfall (a late payment for an order whose stock was already released),
     * whatever is available is taken and the missing units are returned so staff can follow up.
     *
     * @return array<int, array{item: OrderItem, missing: int}> units that could not be reserved
     */
    public function deductForOrder(Order $order, bool $allowShortfall = false, ?User $by = null): array
    {
        return DB::transaction(function () use ($order, $allowShortfall, $by) {
            $shortfalls = [];
            // Lock rows in a fixed order so concurrent checkouts can't deadlock
            $items = $order->items()->get()->sortBy(fn ($i) => [$i->product_id, $i->variant_id ?? 0]);

            foreach ($items as $item) {
                [$product, $variant] = $this->lock(
                    Product::withTrashed()->findOrFail($item->product_id),
                    $item->variant_id ? ProductVariant::find($item->variant_id) : null,
                    requireVariant: false,
                );

                $available = $variant ? $variant->stock_quantity : $product->stock_quantity;
                $take = $item->quantity;

                if ($available < $item->quantity) {
                    if (! $allowShortfall) {
                        throw new InsufficientStockException($this->itemName($product, $variant), max(0, $available), $item->quantity);
                    }
                    $take = max(0, $available);
                    $shortfalls[] = ['item' => $item, 'missing' => $item->quantity - $take];
                }

                $cost = $variant ? $variant->effectiveCost() : (float) $product->cost_price;
                if ($item->unit_cost === null) {
                    $item->update(['unit_cost' => $cost]);
                }

                if ($take > 0) {
                    $this->apply($product, $variant, -$take, 'sale', [
                        'order_id' => $order->id,
                        'user_id' => $by?->id,
                        'unit_cost' => (float) ($item->unit_cost ?? $cost),
                        'note' => 'Order ' . $order->order_number,
                    ]);
                }
            }

            if ($order->stock_released_at) {
                $order->forceFill(['stock_released_at' => null])->save();
            }

            return $shortfalls;
        });
    }

    /**
     * Put an order's stock back (order cancelled before it left the store, or left unpaid).
     * Units already recorded as returns are not put back twice. Safe to call more than once.
     */
    public function releaseOrder(Order $order, string $note, ?User $by = null): bool
    {
        return DB::transaction(function () use ($order, $note, $by) {
            // Claim the release first, so two requests can't both restock the order
            $claimed = Order::whereKey($order->id)->whereNull('stock_released_at')->update(['stock_released_at' => now()]);
            if (! $claimed) {
                return false;
            }

            $hasSaleMovements = $order->inventoryMovements()->where('type', 'sale')->exists();
            $items = $order->items()->withSum('returns as returned_quantity', 'quantity')->get()
                ->sortBy(fn ($i) => [$i->product_id, $i->variant_id ?? 0]);

            foreach ($items as $item) {
                // Only put back what this order actually took out of stock
                $taken = $hasSaleMovements
                    ? (int) -$order->inventoryMovements()->where('product_id', $item->product_id)
                        ->where('variant_id', $item->variant_id)->whereIn('type', ['sale', 'sale_release'])->sum('quantity_change')
                    : $item->quantity; // orders placed before stock tracking began
                $quantity = min($taken, $item->quantity - (int) $item->returned_quantity);

                $product = Product::withTrashed()->find($item->product_id);
                $variant = $item->variant_id ? ProductVariant::find($item->variant_id) : null;
                if ($quantity <= 0 || ! $product || ($product->variants()->exists() && ! $variant)) {
                    continue; // colour deleted since: nothing to put the stock back into
                }

                [$product, $variant] = $this->lock($product, $variant);
                $this->apply($product, $variant, $quantity, 'sale_release', [
                    'order_id' => $order->id,
                    'user_id' => $by?->id,
                    'unit_cost' => (float) ($item->unit_cost ?? 0),
                    'note' => $note,
                ]);
            }

            $order->stock_released_at = now();

            return true;
        });
    }

    /**
     * Record items a customer returned.
     *  - resellable: back into sellable stock
     *  - damaged: NOT back into stock; the purchase cost is recorded as a loss
     * The refund (default: what the customer paid for those units) reduces revenue in reports.
     */
    public function recordReturn(OrderItem $item, int $quantity, string $condition, ?float $refund = null, ?string $reason = null, ?string $notes = null, ?User $by = null): ProductReturn
    {
        if (! array_key_exists($condition, ProductReturn::CONDITIONS)) {
            throw ValidationException::withMessages(['condition' => 'Choose whether the item can be resold.']);
        }

        return DB::transaction(function () use ($item, $quantity, $condition, $refund, $reason, $notes, $by) {
            $item = OrderItem::with('order')->lockForUpdate()->findOrFail($item->id);
            $order = $item->order;

            if ($order->payment_status !== 'paid') {
                throw ValidationException::withMessages(['quantity' => 'Only paid orders can have returns.']);
            }

            $returnable = $item->returnableQuantity();
            if ($quantity < 1 || $quantity > $returnable) {
                throw ValidationException::withMessages(['quantity' => $returnable > 0
                    ? "You can return between 1 and {$returnable} of this item."
                    : 'Everything on this line has already been returned.']);
            }

            $product = Product::withTrashed()->findOrFail($item->product_id);
            $variant = $item->variant_id ? ProductVariant::find($item->variant_id) : null;

            if ($condition === 'resellable' && $product->variants()->exists() && ! $variant) {
                throw ValidationException::withMessages(['condition' => 'This colour no longer exists, so it can\'t go back into stock. Record it as damaged, or add the stock to another colour by hand.']);
            }

            $unitCost = (float) ($item->unit_cost ?? ($variant ? $variant->effectiveCost() : $product->cost_price));
            $maxRefund = round($quantity * (float) $item->price, 2);
            $refund = $refund === null ? $maxRefund : round($refund, 2);

            if ($refund < 0 || $refund > $maxRefund) {
                throw ValidationException::withMessages(['refund_amount' => 'The refund must be between 0 and ' . number_format($maxRefund, 2) . ' (what the customer paid for these units).']);
            }

            $return = ProductReturn::create([
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity' => $quantity,
                'item_condition' => $condition,
                'unit_price' => $item->price,
                'unit_cost' => $unitCost,
                'refund_amount' => $refund,
                'reason' => $reason,
                'notes' => $notes,
                'processed_by' => $by?->id,
                'processed_at' => now(),
            ]);

            $attributes = [
                'order_id' => $order->id,
                'product_return_id' => $return->id,
                'user_id' => $by?->id,
                'unit_cost' => $unitCost,
                'note' => trim('Return on order ' . $order->order_number . ($reason ? ": $reason" : '')),
            ];

            if ($condition === 'resellable') {
                [$product, $variant] = $this->lock($product, $variant);
                $this->apply($product, $variant, $quantity, 'return_restock', $attributes);
            } else {
                // Recorded for the audit trail only: damaged units never re-enter sellable stock
                $this->record($product, $variant, 0, $quantity, 'return_damaged', $attributes);
            }

            return $return;
        });
    }

    /**
     * Log a stock change that was already saved elsewhere (the product edit form).
     */
    public function recordExternalChange(Product $product, ?ProductVariant $variant, int $before, int $after, string $type, ?string $note, ?User $by): ?InventoryMovement
    {
        if ($before === $after) {
            return null;
        }

        return $this->record($product, $variant, $after - $before, abs($after - $before), $type, [
            'note' => $note,
            'user_id' => $by?->id,
            'stock_after' => $after,
        ]);
    }

    /**
     * Lock the stock rows and return fresh copies.
     */
    private function lock(Product $product, ?ProductVariant $variant, bool $requireVariant = true): array
    {
        $product = Product::withTrashed()->lockForUpdate()->findOrFail($product->id);

        if ($variant) {
            $variant = ProductVariant::where('product_id', $product->id)->lockForUpdate()->findOrFail($variant->id);
        } elseif ($requireVariant && $product->variants()->exists()) {
            throw new InvalidArgumentException("Choose a colour of {$product->name}: its stock is kept per colour.");
        }

        return [$product, $variant];
    }

    /**
     * Change stock on locked rows and write the movement.
     */
    private function apply(Product $product, ?ProductVariant $variant, int $change, string $type, array $attributes): InventoryMovement
    {
        $current = $variant ? $variant->stock_quantity : $product->stock_quantity;
        $new = $current + $change;

        if ($new < 0) {
            throw new InsufficientStockException($this->itemName($product, $variant), max(0, $current), abs($change));
        }

        if ($variant) {
            $variant->forceFill(['stock_quantity' => $new])->save();
            // The product's stock is the total of its colours
            $product->forceFill(['stock_quantity' => (int) $product->variants()->sum('stock_quantity')])->save();
        } else {
            $product->forceFill(['stock_quantity' => $new])->save();
        }

        return $this->record($product, $variant, $change, abs($change), $type, $attributes + ['stock_after' => $new]);
    }

    private function record(Product $product, ?ProductVariant $variant, int $change, int $quantity, string $type, array $attributes): InventoryMovement
    {
        $stockAfter = $attributes['stock_after'] ?? ($variant ? $variant->stock_quantity : $product->stock_quantity);
        $unitCost = $attributes['unit_cost'] ?? ($variant ? $variant->effectiveCost() : (float) $product->cost_price);

        return InventoryMovement::create([
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'type' => $type,
            'quantity_change' => $change,
            'quantity' => $quantity,
            'stock_after' => $stockAfter,
            'unit_cost' => $unitCost,
            'order_id' => $attributes['order_id'] ?? null,
            'product_return_id' => $attributes['product_return_id'] ?? null,
            'user_id' => $attributes['user_id'] ?? null,
            'note' => isset($attributes['note']) ? mb_substr((string) $attributes['note'], 0, 255) : null,
            'created_at' => now(),
        ]);
    }

    private function itemName(Product $product, ?ProductVariant $variant): string
    {
        return $product->name . ($variant ? ' (' . $variant->color_name . ')' : '');
    }
}
