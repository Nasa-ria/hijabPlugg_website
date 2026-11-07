<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Items a customer sent back. Created through InventoryService::recordReturn().
 */
class ProductReturn extends Model
{
    public const CONDITIONS = [
        'resellable' => 'Resellable (back into stock)',
        'damaged' => 'Damaged (cannot be resold)',
    ];

    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_id',
        'variant_id',
        'quantity',
        'item_condition',
        'unit_price',
        'unit_cost',
        'refund_amount',
        'reason',
        'notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isDamaged(): bool
    {
        return $this->item_condition === 'damaged';
    }

    /** Purchase cost written off for a damaged return (GHS) */
    public function lossAmount(): float
    {
        return $this->isDamaged() ? round($this->quantity * (float) $this->unit_cost, 2) : 0.0;
    }
}
