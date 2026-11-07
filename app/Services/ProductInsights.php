<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Storefront popularity data: best sellers, new arrivals and recommendations.
 * Recommendations only ever contain products a customer can buy right now.
 */
class ProductInsights
{
    private static ?array $bestSellerIds = null;

    /**
     * IDs of the top-selling products over the configured period (cached for an hour).
     */
    public static function bestSellerIds(): array
    {
        return self::$bestSellerIds ??= Cache::remember('shop:best-seller-ids', now()->addHour(), function () {
            return OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.payment_status', 'paid')
                ->where('orders.paid_at', '>=', now()->subDays((int) config('shop.storefront.best_seller_days', 90)))
                ->selectRaw('order_items.product_id, SUM(order_items.quantity) as units')
                ->groupBy('order_items.product_id')
                ->orderByDesc('units')
                ->limit((int) config('shop.storefront.best_seller_count', 8))
                ->pluck('order_items.product_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        });
    }

    public static function flush(): void
    {
        self::$bestSellerIds = null;
        Cache::forget('shop:best-seller-ids');
    }

    /**
     * Adds "units_sold" (paid orders, all time) to a product query, for the popularity sort.
     */
    public static function withUnitsSold(Builder $query): Builder
    {
        return $query->withSum(['orderItems as units_sold' => fn ($q) => $q->whereHas('order', fn ($o) => $o->where('payment_status', 'paid'))], 'quantity');
    }

    public function bestSellers(int $limit = 8, array $exclude = []): Collection
    {
        $ids = array_values(array_diff(self::bestSellerIds(), $exclude));
        if (empty($ids)) {
            return new Collection();
        }

        return $this->base()->whereIn('id', $ids)->get()
            ->sortBy(fn ($p) => array_search($p->id, $ids, true))->take($limit)->values();
    }

    public function newArrivals(int $limit = 8, array $exclude = []): Collection
    {
        return $this->base()->whereNotIn('id', $exclude)->latest()->limit($limit)->get();
    }

    public function fromCategories(array $categoryIds, int $limit = 8, array $exclude = []): Collection
    {
        if (empty($categoryIds)) {
            return new Collection();
        }

        return $this->base()->whereIn('category_id', $categoryIds)->whereNotIn('id', $exclude)
            ->orderByDesc('average_rating')->latest()->limit($limit)->get();
    }

    /**
     * "You may also like" for a product page: best sellers and new pieces outside the
     * related-products list, so the two rows don't repeat each other.
     */
    public function recommendedFor(Product $product, array $exclude = [], int $limit = 4): Collection
    {
        $exclude = array_merge($exclude, [$product->id]);
        $picks = $this->bestSellers($limit, $exclude);

        if ($picks->count() < $limit) {
            $picks = $picks->merge($this->newArrivals($limit - $picks->count(), array_merge($exclude, $picks->pluck('id')->all())));
        }

        return $picks->values();
    }

    /**
     * Purchasable products with what the product cards need.
     */
    public function base(): Builder
    {
        return Product::query()->purchasable()->with(['variants', 'images', 'category']);
    }
}
