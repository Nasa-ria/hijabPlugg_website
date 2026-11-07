<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One stock change. Rows are only ever added (never edited), so this is the audit trail.
 * Write them through App\Services\InventoryService, which also updates the stock.
 */
class InventoryMovement extends Model
{
    public const UPDATED_AT = null;

    /**
     * type => [label, badge classes, counts as a loss?]
     */
    public const TYPES = [
        'opening' => ['Opening stock', 'bg-gray-100 text-gray-700', false],
        'restock' => ['Stock added', 'bg-emerald-50 text-emerald-800', false],
        'import' => ['Excel import', 'bg-emerald-50 text-emerald-800', false],
        'adjustment' => ['Adjustment', 'bg-cream-200 text-brand-800', false],
        'sale' => ['Sold', 'bg-brand-100 text-brand-800', false],
        'sale_release' => ['Order cancelled (restocked)', 'bg-cream-200 text-brand-800', false],
        'return_restock' => ['Return (resellable)', 'bg-accent-100 text-accent-800', false],
        'return_damaged' => ['Return (damaged)', 'bg-red-50 text-red-700', true],
        'damage' => ['Damaged in store', 'bg-red-50 text-red-700', true],
        'write_off' => ['Write-off', 'bg-red-50 text-red-700', true],
    ];

    /** Types an admin can choose when adjusting stock by hand */
    public const MANUAL_TYPES = ['restock', 'adjustment', 'damage', 'write_off'];

    /** Types that count towards "stock added" in reports */
    public const ADDITION_TYPES = ['restock', 'import'];

    /** Types recorded as losses (their quantity × unit cost) */
    public const LOSS_TYPES = ['damage', 'write_off'];

    protected $fillable = [
        'product_id',
        'variant_id',
        'type',
        'quantity_change',
        'quantity',
        'stock_after',
        'unit_cost',
        'order_id',
        'product_return_id',
        'user_id',
        'note',
        'created_at',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'quantity' => 'integer',
        'stock_after' => 'integer',
        'unit_cost' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function productReturn(): BelongsTo
    {
        return $this->belongsTo(ProductReturn::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type][0] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getTypeBadgeAttribute(): string
    {
        return self::TYPES[$this->type][1] ?? 'bg-gray-100 text-gray-700';
    }
}
