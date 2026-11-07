<?php

namespace App\Models;

use App\Services\ProductInsights;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'short_description',
        'material',
        'size',
        'size_chart',
        'price',
        'sale_price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
        'is_available',
        'main_image',
        'average_rating',
        'total_reviews',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'weight_kg' => 'decimal:3',
        'length_cm' => 'decimal:2',
        'width_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Price a customer pays right now (sale price when set).
     */
    public function currentPrice(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    /**
     * The size chart as a table. Entered in the admin as lines like:
     *   Size, Bust, Waist, Length
     *   S, 86 cm, 70 cm, 140 cm
     * Returns ['headers' => [...], 'rows' => [[...], ...]] or null when empty.
     */
    public function sizeChartTable(): ?array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $this->size_chart))));
        if (count($lines) < 2) {
            return null;
        }

        $split = fn ($line) => array_map('trim', str_getcsv($line));

        return [
            'headers' => $split($lines[0]),
            'rows' => array_map($split, array_slice($lines, 1)),
        ];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ProductReturn::class);
    }

    /**
     * Products a customer can buy right now: listed and in stock.
     */
    public function scopePurchasable(Builder $query): Builder
    {
        return $query->where('is_available', true)->where('stock_quantity', '>', 0);
    }

    public function lowStockThreshold(): int
    {
        return $this->low_stock_threshold ?? (int) config('shop.inventory.low_stock_threshold', 5);
    }

    public function inStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->inStock() && $this->stock_quantity <= $this->lowStockThreshold();
    }

    public function isOnSale(): bool
    {
        return $this->sale_price !== null && (float) $this->sale_price < (float) $this->price;
    }

    public function isNewArrival(): bool
    {
        return $this->created_at && $this->created_at->gt(now()->subDays((int) config('shop.storefront.new_arrival_days', 30)));
    }

    /**
     * Storefront badges, most important first: [key => label].
     */
    public function badges(): array
    {
        $badges = [];

        if (! $this->inStock()) {
            return ['sold_out' => 'Sold out'];
        }
        if (in_array($this->id, ProductInsights::bestSellerIds(), true)) {
            $badges['best_seller'] = 'Best Seller';
        }
        if ($this->isOnSale()) {
            $badges['sale'] = 'On Sale';
        }
        if ($this->isNewArrival()) {
            $badges['new'] = 'New Arrival';
        }
        if ($this->isLowStock()) {
            $badges['limited'] = 'Limited Stock';
        }

        return $badges;
    }

    /**
     * Shipping weight of one unit (kg), using the store default when not entered.
     */
    public function shippingWeightKg(): float
    {
        return (float) ($this->weight_kg ?: config('shipping.parcel.default_item_weight_kg'));
    }

    /**
     * Shipping dimensions of one folded unit (cm): [length, width, height].
     */
    public function shippingDimensions(): array
    {
        $defaults = config('shipping.parcel.default_item_dimensions_cm');

        return [
            (float) ($this->length_cm ?: $defaults['length']),
            (float) ($this->width_cm ?: $defaults['width']),
            (float) ($this->height_cm ?: $defaults['height']),
        ];
    }

    public function hasShippingData(): bool
    {
        return (float) $this->weight_kg > 0 && (float) $this->length_cm > 0 && (float) $this->width_cm > 0 && (float) $this->height_cm > 0;
    }

    protected static function booted(): void
    {
        // Every product gets a SKU (needed for stock imports/exports)
        static::created(function (Product $product) {
            if (! $product->sku) {
                $product->forceFill(['sku' => sprintf('NSR-%05d', $product->id)])->saveQuietly();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
