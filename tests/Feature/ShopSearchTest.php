<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\ProductInsights;
use App\Services\ProductSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class ShopSearchTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    private const NO_MATCH = "We couldn't find exactly what you're looking for, but you might like these products.";

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Http::preventStrayRequests();
        $this->fakeRates();
    }

    public function test_shop_page_shows_hero_categories_badges_and_sorting(): void
    {
        $category = $this->category('Chiffon Hijabs');
        $this->product(['name' => 'Dusty Rose Chiffon', 'category' => $category, 'price' => 120, 'sale_price' => 90]);
        $this->product(['name' => 'Last One Standing', 'stock_quantity' => 2]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Modesty,', false)
            ->assertSee('Chiffon Hijabs')
            ->assertSee('On Sale')
            ->assertSee('New Arrival')
            ->assertSee('Limited Stock')
            ->assertSee('Most popular')
            ->assertSee('Newest arrivals')
            ->assertSee('GH₵90.00', false);
    }

    public function test_search_finds_products_by_name_description_category_and_colour(): void
    {
        $category = $this->category('Abayas');
        $this->product(['name' => 'Evening Kaftan', 'category' => $category, 'description' => 'Flowing crepe']);
        $product = $this->product(['name' => 'Classic Square']);
        $this->variant($product, ['color_name' => 'Sage Green']);

        $this->get('/?search=crepe')->assertSee('Evening Kaftan');
        $this->get('/?search=abayas')->assertSee('Evening Kaftan');
        $this->get('/?search=sage')->assertSee('Classic Square')->assertDontSee('Evening Kaftan');
    }

    public function test_related_words_are_searched(): void
    {
        $this->product(['name' => 'Jersey Hijab']);

        $this->get('/?search=scarf')->assertSee('Jersey Hijab')->assertDontSee(self::NO_MATCH, false);
    }

    public function test_spelling_mistakes_get_a_suggestion_and_similar_products(): void
    {
        $this->product(['name' => 'Chiffon Hijab']);

        $this->get('/?search=chifon')
            ->assertOk()
            ->assertSee(self::NO_MATCH, false)
            ->assertSee('Did you mean')
            ->assertSee('chiffon')
            ->assertSee('Similar to your search')
            ->assertSee('Chiffon Hijab');

        $this->assertSame('chiffon', app(ProductSearch::class)->suggestion('chifon'));
    }

    public function test_no_match_recommends_available_products_only(): void
    {
        $category = $this->category('Undercaps');
        $bestSeller = $this->product(['name' => 'Bestselling Wrap', 'category' => $category]);
        $this->product(['name' => 'Sold Out Wrap', 'stock_quantity' => 0, 'category' => $category]);
        $this->product(['name' => 'Hidden Wrap', 'is_available' => false]);
        $order = Order::create(['user_id' => $this->customer()->id, 'subtotal' => 100, 'total_amount' => 105, 'shipping_address' => 'x', 'status' => 'processing', 'payment_status' => 'paid', 'paid_at' => now()]);
        $order->items()->create(['product_id' => $bestSeller->id, 'quantity' => 4, 'price' => 100, 'subtotal' => 400]);
        ProductInsights::flush();

        $this->get('/?search=zzzzqqq')
            ->assertOk()
            ->assertSee(self::NO_MATCH, false)
            ->assertSee('Best sellers')
            ->assertSee('Bestselling Wrap')
            ->assertSee('Clear search')
            ->assertDontSee('Sold Out Wrap')
            ->assertDontSee('Hidden Wrap');
    }

    public function test_popular_sort_orders_by_units_sold(): void
    {
        $slow = $this->product(['name' => 'Slow Seller']);
        $fast = $this->product(['name' => 'Fast Seller']);
        $order = Order::create(['user_id' => $this->customer()->id, 'subtotal' => 100, 'total_amount' => 105, 'shipping_address' => 'x', 'status' => 'processing', 'payment_status' => 'paid', 'paid_at' => now()]);
        $order->items()->create(['product_id' => $fast->id, 'quantity' => 9, 'price' => 100, 'subtotal' => 900]);
        $order->items()->create(['product_id' => $slow->id, 'quantity' => 1, 'price' => 100, 'subtotal' => 100]);

        $this->get('/?sort=popular')->assertSeeInOrder(['Fast Seller', 'Slow Seller']);
        $this->get('/?sort=latest')->assertOk(); // old links still work
    }

    public function test_product_page_shows_related_and_recommended_products(): void
    {
        $category = $this->category('Silk');
        $product = $this->product(['name' => 'Silk Main', 'category' => $category]);
        $this->product(['name' => 'Silk Sibling', 'category' => $category]);
        $this->product(['name' => 'Other Pick']);

        $this->withHeader('CF-IPCountry', 'GH')->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('More from Silk')
            ->assertSee('Silk Sibling')
            ->assertSee('You may also like')
            ->assertSee('Other Pick')
            ->assertSee('You will pay the delivery fee directly upon delivery.');
    }

    public function test_empty_category_has_a_friendly_state(): void
    {
        $category = $this->category('Coming Soon');

        $this->get('/?category=' . $category->slug)->assertOk()->assertSee('Nothing here just yet');
    }

    public function test_shop_works_for_mobile_browsers(): void
    {
        $this->product(['name' => 'Mobile Hijab']);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')
            ->get('/')
            ->assertOk()
            ->assertSee('Mobile Hijab')
            ->assertSee('data-filter-open', false)       // phone filter sheet
            ->assertSee('id="mobile-search"', false)
            ->assertSee('name="viewport"', false);
    }
}
