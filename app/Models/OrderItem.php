<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'color_name',
        'quantity',
        'price',
        'unit_cost',
        'subtotal',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function returns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductReturn::class);
    }

    /**
     * Units of this line that can still be returned.
     */
    public function returnableQuantity(): int
    {
        return max(0, $this->quantity - (int) $this->returns()->sum('quantity'));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
