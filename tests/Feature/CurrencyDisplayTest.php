<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class CurrencyDisplayTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Http::preventStrayRequests();
        $this->fakeRates();
    }

    public function test_ghanaian_customer_sees_prices_in_cedis(): void
    {
        $this->product(['name' => 'Chiffon Rose', 'price' => 120]);

        $this->withHeader('CF-IPCountry', 'GH')->get('/')
            ->assertOk()
            ->assertSee('GH₵120.00', false)
            ->assertDontSee('$9.60', false);
    }

    public function test_international_customer_sees_prices_in_dollars(): void
    {
        $this->product(['name' => 'Chiffon Rose', 'price' => 120]);

        $this->withHeader('CF-IPCountry', 'US')->get('/')
            ->assertOk()
            ->assertSee('$9.60', false) // 120 × 0.08
            ->assertDontSee('GH₵120.00', false);
    }

    public function test_country_on_the_account_wins_over_headers(): void
    {
        $this->product(['price' => 50]);
        $user = $this->customer(['country' => 'Ghana']);

        $this->actingAs($user)->withHeader('CF-IPCountry', 'GB')->get('/')->assertSee('GH₵50.00', false);
    }

    public function test_customer_can_override_the_detected_currency(): void
    {
        $this->product(['price' => 50]);

        $this->withHeader('CF-IPCountry', 'GH')->post(route('currency.update'), ['currency' => 'USD'])->assertRedirect();
        $this->withHeader('CF-IPCountry', 'GH')->get('/')->assertSee('$4.00', false);

        $this->post(route('currency.update'), ['currency' => 'GHS']);
        $this->withHeader('CF-IPCountry', 'US')->get('/')->assertSee('GH₵50.00', false);
    }

    public function test_unsupported_currency_is_rejected(): void
    {
        $this->post(route('currency.update'), ['currency' => 'EUR'])->assertSessionHasErrors('currency');
    }

    public function test_configured_fallback_rate_is_used_when_the_rate_source_is_down(): void
    {
        $this->rateSourceDown();
        config(['currency.rates.fallback.USD' => 0.05]);
        $this->product(['price' => 100]);

        $this->withHeader('CF-IPCountry', 'US')->get('/')->assertSee('$5.00', false);
    }

    public function test_falls_back_to_cedis_when_no_rate_is_known(): void
    {
        $this->rateSourceDown();
        config(['currency.rates.fallback.USD' => null]);
        $this->product(['price' => 100]);

        $this->withHeader('CF-IPCountry', 'US')->get('/')->assertSee('GH₵100.00', false);
    }

    private function rateSourceDown(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(['open.er-api.com/*' => Http::response('down', 503)]);
        Cache::flush();
    }

    public function test_exchange_rates_are_cached(): void
    {
        $service = app(CurrencyService::class);
        $service->rates();
        app()->forgetInstance(CurrencyService::class);
        app(CurrencyService::class)->rates();

        Http::assertSentCount(1);
    }

    public function test_database_amounts_stay_in_cedis_for_international_orders(): void
    {
        $product = $this->product(['price' => 200, 'stock_quantity' => 5]);
        $user = $this->customer();
        Http::fake(['express.api.dhl.com/*' => Http::response($this->dhlResponse(300))]);

        $this->actingAs($user)->withSession($this->cartWith([[$product, 2, null]]))
            ->withHeader('CF-IPCountry', 'US')
            ->post(route('checkout.store'), $this->checkoutFields(['country' => 'US', 'city' => 'New York', 'postal_code' => '10001']))
            ->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('GHS', $order->currency);
        $this->assertSame('USD', $order->display_currency);
        $this->assertEquals(0.08, (float) $order->display_exchange_rate);
        $this->assertEquals(400.00, (float) $order->subtotal);           // GHS, not converted
        $this->assertEquals(300.00, (float) $order->shipping_cost);      // GHS
        $this->assertEquals(400 + 20 + 300, (float) $order->total_amount);
        $this->assertEquals(200.00, (float) $order->items->first()->price);
        $this->assertEquals(200.00, (float) $product->fresh()->price);   // product price untouched
    }
}
