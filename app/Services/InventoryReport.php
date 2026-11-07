<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Stock and money figures for the dashboard, inventory pages and Excel export. All GHS.
 *
 *   Gross sales     = product sales of paid orders (shipping and tax excluded)
 *   Refunds         = money given back for returns
 *   Revenue         = gross sales − refunds
 *   COGS            = units sold × purchase cost at the time of sale − cost of units returned
 *   Gross profit    = revenue − COGS
 *   Losses          = cost of damaged returns + damage and write-offs recorded in stock
 *   Net profit      = gross profit − losses − payment fees
 *
 * Shipping collected/paid, tax and Paystack fees are reported on their own lines and never
 * mixed into revenue. Sales count on the day they were paid; returns on the day they were processed,
 * so a return never changes an earlier period and revenue is never counted twice.
 *
 * Filters: ['category_id' => int|null, 'product_id' => int|null]
 */
class InventoryReport
{
    /**
     * Every stock-keeping unit (a product, or each colour of a product with colours) with its current stock.
     */
    public function units(array $filters = [], bool $withTrashed = false): Collection
    {
        $products = Product::query()
            ->when($withTrashed, fn ($q) => $q->withTrashed())
            ->with(['category', 'variants'])
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['product_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->orderBy('name')
            ->get();

        $rows = collect();
        foreach ($products as $product) {
            $threshold = $product->lowStockThreshold();

            if ($product->variants->isEmpty()) {
                $rows->push($this->unitRow($product, null, $product->stock_quantity, (float) $product->cost_price, $product->currentPrice(), $threshold));
                continue;
            }

            foreach ($product->variants as $variant) {
                $variant->setRelation('product', $product);
                $rows->push($this->unitRow($product, $variant, $variant->stock_quantity, $variant->effectiveCost(), $variant->effectivePrice(), $threshold));
            }
        }

        return $rows;
    }

    private function unitRow(Product $product, $variant, int $stock, float $cost, float $price, int $threshold): object
    {
        return (object) [
            'key' => $product->id . ':' . ($variant?->id ?? 0),
            'product' => $product,
            'variant' => $variant,
            'sku' => $variant?->sku ?: $product->sku,
            'name' => $product->name,
            'colour' => $variant?->color_name,
            'category' => $product->category?->name,
            'stock' => $stock,
            'cost' => $cost,
            'price' => $price,
            'value' => round($stock * $cost, 2),
            'threshold' => $threshold,
            'is_out' => $stock <= 0,
            'is_low' => $stock > 0 && $stock <= $threshold,
        ];
    }

    /**
     * Current stock position: units, valuation (at purchase cost), low and out of stock.
     */
    public function stockPosition(array $filters = []): array
    {
        $units = $this->units($filters)->filter(fn ($u) => $u->product->is_available || $u->stock > 0);

        return [
            'units_in_stock' => (int) $units->sum(fn ($u) => max(0, $u->stock)),
            'valuation' => round($units->sum(fn ($u) => max(0, $u->stock) * $u->cost), 2),
            'retail_value' => round($units->sum(fn ($u) => max(0, $u->stock) * $u->price), 2),
            'low_stock' => $units->where('is_low', true)->values(),
            'out_of_stock' => $units->where('is_out', true)->values(),
            'missing_cost' => $units->filter(fn ($u) => $u->cost <= 0)->count(),
        ];
    }

    /**
     * Money summary for a period.
     */
    public function summary(Carbon $from, Carbon $to, array $filters = []): array
    {
        $sales = $this->paidItems($from, $to, $filters)
            ->selectRaw('COALESCE(SUM(oi.quantity), 0) as units')
            ->selectRaw('COALESCE(SUM(oi.subtotal), 0) as gross')
            ->selectRaw('COALESCE(SUM(oi.quantity * ' . $this->costSql() . '), 0) as cogs')
            ->selectRaw('COUNT(DISTINCT oi.order_id) as orders')
            ->first();

        $returns = $this->returns($from, $to, $filters)
            ->selectRaw('COALESCE(SUM(r.quantity), 0) as units')
            ->selectRaw('COALESCE(SUM(r.refund_amount), 0) as refunds')
            ->selectRaw('COALESCE(SUM(r.quantity * r.unit_cost), 0) as cost')
            ->selectRaw("COALESCE(SUM(CASE WHEN r.item_condition = 'damaged' THEN r.quantity ELSE 0 END), 0) as damaged_units")
            ->selectRaw("COALESCE(SUM(CASE WHEN r.item_condition = 'damaged' THEN r.quantity * r.unit_cost ELSE 0 END), 0) as damaged_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN r.item_condition = 'damaged' THEN 0 ELSE r.quantity END), 0) as restocked_units")
            ->first();

        $writeOffs = $this->movements($from, $to, $filters)->whereIn('m.type', InventoryMovement::LOSS_TYPES)
            ->selectRaw('COALESCE(SUM(m.quantity), 0) as units, COALESCE(SUM(m.quantity * m.unit_cost), 0) as cost')
            ->first();

        $gross = (float) $sales->gross;
        $refunds = (float) $returns->refunds;
        $revenue = $gross - $refunds;
        $cogs = (float) $sales->cogs - (float) $returns->cost;
        $grossProfit = $revenue - $cogs;
        $losses = (float) $returns->damaged_cost + (float) $writeOffs->cost;

        // Order-level amounts can't be split by product, so they only show unfiltered
        $orderLevel = empty($filters['category_id']) && empty($filters['product_id']);
        $orders = $orderLevel ? DB::table('orders')->where('payment_status', 'paid')->whereNull('deleted_at')
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(shipping_cost), 0) as shipping_collected')
            ->selectRaw('COALESCE(SUM(shipping_final_cost), 0) as shipping_paid')
            ->selectRaw('COALESCE(SUM(CASE WHEN shipping_final_cost IS NULL AND shipping_cost > 0 THEN 1 ELSE 0 END), 0) as shipping_unconfirmed')
            ->selectRaw('COALESCE(SUM(tax), 0) as tax')
            ->selectRaw('COALESCE(SUM(payment_fees), 0) as fees')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as collected')
            ->first() : null;

        $fees = $orders ? (float) $orders->fees : 0.0;

        return [
            'order_level' => $orderLevel,
            'orders' => (int) $sales->orders,
            'units_sold' => (int) $sales->units - (int) $returns->units,
            'units_sold_gross' => (int) $sales->units,
            'gross_sales' => round($gross, 2),
            'refunds' => round($refunds, 2),
            'revenue' => round($revenue, 2),
            'cogs' => round($cogs, 2),
            'gross_profit' => round($grossProfit, 2),
            'margin' => $revenue > 0 ? round($grossProfit / $revenue * 100, 1) : null,
            'losses' => round($losses, 2),
            'loss_units' => (int) $returns->damaged_units + (int) $writeOffs->units,
            'returned_units' => (int) $returns->units,
            'returned_value' => round((float) $returns->refunds, 2),
            'restocked_units' => (int) $returns->restocked_units,
            'damaged_units' => (int) $returns->damaged_units,
            'write_off_units' => (int) $writeOffs->units,
            'payment_fees' => round($fees, 2),
            'net_profit' => round($grossProfit - $losses - $fees, 2),
            'shipping_collected' => $orders ? round((float) $orders->shipping_collected, 2) : null,
            'shipping_paid' => $orders ? round((float) $orders->shipping_paid, 2) : null,
            'shipping_unconfirmed' => $orders ? (int) $orders->shipping_unconfirmed : null,
            'tax_collected' => $orders ? round((float) $orders->tax, 2) : null,
            'total_collected' => $orders ? round((float) $orders->collected, 2) : null,
        ];
    }

    /**
     * Revenue, cost and profit per day (or per hour) for charts.
     */
    public function trend(Carbon $from, Carbon $to, bool $hourly, array $filters = []): array
    {
        $bucket = fn (string $column) => $this->bucketSql($column, $hourly);

        $sales = $this->paidItems($from, $to, $filters)
            ->selectRaw($bucket('o.paid_at') . ' as bucket')
            ->selectRaw('SUM(oi.subtotal) as gross, SUM(oi.quantity * ' . $this->costSql() . ') as cogs')
            ->groupBy('bucket')->get()->keyBy('bucket');

        $returns = $this->returns($from, $to, $filters)
            ->selectRaw($bucket('r.processed_at') . ' as bucket')
            ->selectRaw('SUM(r.refund_amount) as refunds, SUM(r.quantity * r.unit_cost) as cost')
            ->groupBy('bucket')->get()->keyBy('bucket');

        $labels = $revenue = $profit = $cost = [];
        foreach (CarbonPeriod::create($hourly ? $from->copy()->startOfHour() : $from->copy()->startOfDay(), $hourly ? '1 hour' : '1 day', $to) as $point) {
            $key = $point->format($hourly ? 'Y-m-d H' : 'Y-m-d');
            $r = (float) ($sales[$key]->gross ?? 0) - (float) ($returns[$key]->refunds ?? 0);
            $c = (float) ($sales[$key]->cogs ?? 0) - (float) ($returns[$key]->cost ?? 0);
            $labels[] = $point->format($hourly ? 'D ga' : 'j M');
            $revenue[] = round($r, 2);
            $cost[] = round($c, 2);
            $profit[] = round($r - $c, 2);
        }

        return compact('labels', 'revenue', 'cost', 'profit');
    }

    /**
     * One row per stock-keeping unit for the period: the Excel export and margin rankings.
     */
    public function breakdown(Carbon $from, Carbon $to, array $filters = []): Collection
    {
        $key = fn ($row) => $row->product_id . ':' . ((int) $row->variant_id);

        $sales = $this->paidItems($from, $to, $filters)
            ->selectRaw('oi.product_id, oi.variant_id, SUM(oi.quantity) as units, SUM(oi.subtotal) as gross')
            ->selectRaw('SUM(oi.quantity * ' . $this->costSql() . ') as cogs')
            ->groupBy('oi.product_id', 'oi.variant_id')->get()->keyBy($key);

        $returns = $this->returns($from, $to, $filters)
            ->selectRaw('r.product_id, r.variant_id, SUM(r.quantity) as units, SUM(r.refund_amount) as refunds, SUM(r.quantity * r.unit_cost) as cost')
            ->selectRaw("SUM(CASE WHEN r.item_condition = 'damaged' THEN r.quantity ELSE 0 END) as damaged_units")
            ->selectRaw("SUM(CASE WHEN r.item_condition = 'damaged' THEN r.quantity * r.unit_cost ELSE 0 END) as damaged_cost")
            ->groupBy('r.product_id', 'r.variant_id')->get()->keyBy($key);

        $additions = implode("','", InventoryMovement::ADDITION_TYPES);
        $losses = implode("','", InventoryMovement::LOSS_TYPES);
        $stock = $this->movements(null, $to, $filters)
            ->selectRaw('m.product_id, m.variant_id')
            ->selectRaw("SUM(CASE WHEN m.created_at < ? OR m.type = 'opening' THEN m.quantity_change ELSE 0 END) as opening", [$from])
            ->selectRaw("SUM(CASE WHEN m.created_at >= ? AND m.type IN ('$additions') THEN m.quantity_change ELSE 0 END) as added", [$from])
            ->selectRaw("SUM(CASE WHEN m.created_at >= ? AND m.type = 'adjustment' THEN m.quantity_change ELSE 0 END) as adjusted", [$from])
            ->selectRaw("SUM(CASE WHEN m.created_at >= ? AND m.type IN ('$losses') THEN m.quantity ELSE 0 END) as written_off", [$from])
            ->selectRaw("SUM(CASE WHEN m.created_at >= ? AND m.type IN ('$losses') THEN m.quantity * m.unit_cost ELSE 0 END) as written_off_cost", [$from])
            ->selectRaw('SUM(m.quantity_change) as closing')
            ->groupBy('m.product_id', 'm.variant_id')->get()->keyBy($key);

        $units = $this->units($filters, withTrashed: true)->keyBy('key');

        // Units that only appear in history (e.g. a colour deleted since) are reported against the product
        foreach ([$sales, $returns, $stock] as $set) {
            foreach ($set->keys() as $k) {
                if (! $units->has($k)) {
                    $productId = (int) explode(':', $k)[0];
                    $product = Product::withTrashed()->with('category')->find($productId);
                    if ($product) {
                        $units[$k] = $this->unitRow($product, null, 0, (float) $product->cost_price, $product->currentPrice(), $product->lowStockThreshold());
                        $units[$k]->key = $k;
                    }
                }
            }
        }

        $live = $to->isFuture() || $to->isToday();
        $period = $from->format('Y-m-d') . ' – ' . $to->format('Y-m-d');

        return $units->map(function ($unit) use ($sales, $returns, $stock, $live, $period) {
            $s = $sales[$unit->key] ?? null;
            $r = $returns[$unit->key] ?? null;
            $m = $stock[$unit->key] ?? null;

            $gross = (float) ($s->gross ?? 0);
            $refunds = (float) ($r->refunds ?? 0);
            $cogs = (float) ($s->cogs ?? 0) - (float) ($r->cost ?? 0);
            $revenue = $gross - $refunds;
            $loss = (float) ($r->damaged_cost ?? 0) + (float) ($m->written_off_cost ?? 0);

            return (object) [
                'sku' => $unit->sku,
                'name' => $unit->name . ($unit->colour ? ' – ' . $unit->colour : ''),
                'product_id' => $unit->product->id,
                'category' => $unit->category,
                'opening_stock' => (int) ($m->opening ?? 0),
                'stock_added' => (int) ($m->added ?? 0),
                'adjustments' => (int) ($m->adjusted ?? 0),
                'quantity_sold' => (int) ($s->units ?? 0),
                'quantity_returned' => (int) ($r->units ?? 0),
                'damaged_quantity' => (int) ($r->damaged_units ?? 0) + (int) ($m->written_off ?? 0),
                // Up to today the live count is exact; for past periods, rebuilt from the history
                'remaining_stock' => $live ? $unit->stock : (int) ($m->closing ?? 0),
                'purchase_cost' => round($unit->cost, 2),
                'selling_price' => round($unit->price, 2),
                'total_sales' => round($revenue, 2),
                'gross_sales' => round($gross, 2),
                'refunds' => round($refunds, 2),
                'total_cost' => round($cogs, 2),
                'profit' => round($revenue - $cogs, 2),
                'loss' => round($loss, 2),
                'margin' => $revenue > 0 ? round(($revenue - $cogs) / $revenue * 100, 1) : null,
                'period' => $period,
            ];
        })->values();
    }

    /**
     * Products with the highest profit margin in the period (only those that sold).
     */
    public function topMargins(Carbon $from, Carbon $to, array $filters = [], int $limit = 6): Collection
    {
        return $this->breakdown($from, $to, $filters)
            ->filter(fn ($row) => $row->quantity_sold > 0 && $row->margin !== null)
            ->sortByDesc(fn ($row) => [$row->margin, $row->profit])
            ->take($limit)->values();
    }

    private function paidItems(Carbon $from, Carbon $to, array $filters): Builder
    {
        return DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->leftJoin('product_variants as v', 'v.id', '=', 'oi.variant_id')
            ->where('o.payment_status', 'paid')
            ->whereNull('o.deleted_at')
            ->whereBetween('o.paid_at', [$from, $to])
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('p.category_id', $id))
            ->when($filters['product_id'] ?? null, fn ($q, $id) => $q->where('oi.product_id', $id));
    }

    private function returns(Carbon $from, Carbon $to, array $filters): Builder
    {
        return DB::table('product_returns as r')
            ->join('products as p', 'p.id', '=', 'r.product_id')
            ->whereBetween('r.processed_at', [$from, $to])
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('p.category_id', $id))
            ->when($filters['product_id'] ?? null, fn ($q, $id) => $q->where('r.product_id', $id));
    }

    private function movements(?Carbon $from, Carbon $to, array $filters): Builder
    {
        return DB::table('inventory_movements as m')
            ->join('products as p', 'p.id', '=', 'm.product_id')
            ->when($from, fn ($q) => $q->where('m.created_at', '>=', $from))
            ->where('m.created_at', '<=', $to)
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('p.category_id', $id))
            ->when($filters['product_id'] ?? null, fn ($q, $id) => $q->where('m.product_id', $id));
    }

    /**
     * Unit cost of a sold item: frozen at sale time; older orders fall back to today's cost.
     */
    private function costSql(): string
    {
        return 'COALESCE(oi.unit_cost, v.cost_price, p.cost_price, 0)';
    }

    private function bucketSql(string $column, bool $hourly): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "to_char($column, '" . ($hourly ? 'YYYY-MM-DD HH24' : 'YYYY-MM-DD') . "')",
            'sqlite' => "strftime('" . ($hourly ? '%Y-%m-%d %H' : '%Y-%m-%d') . "', $column)",
            default => "DATE_FORMAT($column, '" . ($hourly ? '%Y-%m-%d %H' : '%Y-%m-%d') . "')",
        };
    }
}
