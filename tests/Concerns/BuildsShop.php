<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\ProductInsights;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Small fixtures for storefront, inventory and checkout tests.
 */
trait BuildsShop
{
    /** USD per 1 GHS used by the fake exchange-rate source */
    protected float $usdRate = 0.08;

    protected function setUpShop(): void
    {
        Cache::flush();
        ProductInsights::flush();
        app()->forgetInstance(CurrencyService::class);

        config([
            'currency.detection.lookup_url' => '', // never call a real IP service
            'shipping.dhl.api_key' => 'key',
            'shipping.dhl.api_secret' => 'secret',
            'shipping.dhl.account_number' => '123456789',
            'shipping.dhl.environment' => 'test',
            'shipping.origin.city' => 'Accra',
            'shipping.origin.postal_code' => '00233',
            'payment.paystack_secret_key' => 'sk_test_x',
            'shop.tax_rate' => 0.05,
        ]);
    }

    protected function fakeRates(?float $usd = null): void
    {
        Http::fake([
            'open.er-api.com/*' => Http::response(['result' => 'success', 'rates' => ['GHS' => 1, 'USD' => $usd ?? $this->usdRate, 'EUR' => 0.074]]),
        ]);
    }

    protected function category(string $name = 'Hijabs'): Category
    {
        return Category::create(['name' => $name, 'slug' => Str::slug($name) . '-' . Str::random(4), 'is_active' => true]);
    }

    protected function product(array $attributes = []): Product
    {
        $category = $attributes['category'] ?? $this->category();
        unset($attributes['category']);
        $name = $attributes['name'] ?? 'Chiffon Hijab ' . Str::random(4);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'description' => 'Soft breathable chiffon.',
            'price' => 100,
            'cost_price' => 40,
            'stock_quantity' => 10,
            'is_available' => true,
            'main_image' => 'products/test.jpg',
            'weight_kg' => 0.25,
            'length_cm' => 30,
            'width_cm' => 20,
            'height_cm' => 2,
        ], $attributes));
    }

    protected function variant(Product $product, array $attributes = []): ProductVariant
    {
        $variant = $product->variants()->create(array_merge([
            'color_name' => 'Rose ' . Str::random(3),
            'stock_quantity' => 5,
            'image' => 'products/variants/rose.jpg',
        ], $attributes));
        $product->update(['stock_quantity' => $product->variants()->sum('stock_quantity')]);

        return $variant;
    }

    protected function customer(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Amina Customer',
            'email' => Str::random(8) . '@example.com',
            'password' => 'password123',
            'role' => 'customer',
            'is_active' => true,
        ], $attributes));
    }

    protected function admin(): User
    {
        return User::create([
            'name' => 'Store Admin',
            'email' => Str::random(8) . '@nasaari.test',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
            'is_super_admin' => true,
        ]);
    }

    /**
     * Put items in the session cart, the same shape CartController builds.
     */
    protected function cartWith(array $lines): array
    {
        $cart = [];
        foreach ($lines as [$product, $quantity, $variant]) {
            $variant ??= null;
            $key = 'product_' . $product->id . ($variant ? '_variant_' . $variant->id : '');
            $cart[$key] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'color_name' => $variant?->color_name,
                'name' => $product->name,
                'price' => $variant ? $variant->effectivePrice() : $product->currentPrice(),
                'image' => $product->main_image,
                'quantity' => $quantity,
            ];
        }

        return ['cart' => $cart];
    }

    protected function checkoutFields(array $overrides = []): array
    {
        return array_merge([
            'email' => 'amina@example.com',
            'phone' => '+233241234567',
            'shipping_address' => '12 Oxford Street',
            'city' => 'Accra',
            'postal_code' => 'GA-123-4567',
            'country' => 'GH',
        ], $overrides);
    }

    /**
     * A DHL /rates response with one product priced in the given currency.
     */
    protected function dhlResponse(float $price = 450.00, string $currency = 'GHS'): array
    {
        return [
            'products' => [
                [
                    'productName' => 'EXPRESS WORLDWIDE',
                    'productCode' => 'P',
                    'totalPrice' => [
                        ['currencyType' => 'BILLC', 'priceCurrency' => $currency, 'price' => $price],
                        ['currencyType' => 'PULCL', 'priceCurrency' => 'GHS', 'price' => $price],
                    ],
                    'weight' => ['volumetric' => 0.6, 'provided' => 0.7, 'unitOfMeasurement' => 'metric'],
                    'deliveryCapabilities' => ['estimatedDeliveryDateAndTime' => now()->addDays(4)->format('Y-m-d\TH:i:s'), 'totalTransitDays' => '4'],
                ],
                [
                    'productName' => 'EXPRESS 12:00',
                    'productCode' => 'Y',
                    'totalPrice' => [['currencyType' => 'BILLC', 'priceCurrency' => $currency, 'price' => $price + 200]],
                ],
            ],
        ];
    }
}
