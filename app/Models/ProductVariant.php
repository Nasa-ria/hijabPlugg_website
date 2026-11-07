<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'color_name',
        'sku',
        'color_hex',
        'image',
        'price',
        'cost_price',
        'stock_quantity',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Price for this colour: its own price if set, otherwise the product's current price.
     */
    public function effectivePrice(): float
    {
        return (float) ($this->price ?? $this->product->currentPrice());
    }

    /**
     * Purchase cost of one unit (GHS): this colour's own cost if set, otherwise the product's.
     */
    public function effectiveCost(): float
    {
        return (float) ($this->cost_price ?? $this->product->cost_price ?? 0);
    }

    protected static function booted(): void
    {
        static::created(function (ProductVariant $variant) {
            if (! $variant->sku) {
                $code = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::limit(\Illuminate\Support\Str::slug($variant->color_name, ''), 8, '')) ?: 'C';
                $variant->forceFill(['sku' => sprintf('NSR-%05d-%s-%d', $variant->product_id, $code, $variant->id)])->saveQuietly();
            }
        });
    }

    /**
     * Image path for this colour, falling back to the product's main image.
     */
    public function imagePath(): ?string
    {
        return $this->image ?: $this->product->main_image;
    }
}
