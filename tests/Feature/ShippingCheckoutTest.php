<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Shipping\ParcelBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class ShippingCheckoutTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    private const POD_NOTICE = 'Shipping fees are not included in your order total. You will pay the delivery fee directly upon delivery.';
    private const DHL_NOTICE = 'The displayed amount is an estimate provided by DHL and may vary depending on the final shipment measurements.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Http::preventStrayRequests();
        $this->fakeRates();
        Mail::fake();
    }

    public function test_ghana_cart_and_checkout_show_the_pay_on_delivery_notice(): void
    {
        $product = $this->product(['price' => 100]);
        $user = $this->customer();
        $session = $this->cartWith([[$product, 1, null]]);

        $this->withHeader('CF-IPCountry', 'GH')->withSession($session)->get(route('cart.index'))
            ->assertOk()->assertSee(self::POD_NOTICE)->assertSee('Paid on delivery')
            ->assertSee('GH₵105.00', false); // 100 + 5% tax, no shipping

        $this->actingAs($user)->withHeader('CF-IPCountry', 'GH')->withSession($session)->get(route('checkout.index'))
            ->assertOk()->assertSee(self::POD_NOTICE)->assertSee('GH₵105.00', false);
    }

    public function test_ghana_order_is_not_charged_for_shipping(): void
    {
        $product = $this->product(['price' => 100, 'stock_quantity' => 3]);
        $user = $this->customer();

        $this->actingAs($user)->withSession($this->cartWith([[$product, 2, null]]))
            ->post(route('checkout.store'), $this->checkoutFields())
            ->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame(Order::SHIPPING_POD, $order->shipping_method);
        $this->assertEquals(0, (float) $order->shipping_cost);
        $this->assertNull($order->shipping_estimate);
        $this->assertEquals(210.00, (float) $order->total_amount); // 200 + 10 tax
        $this->assertEquals(210.00, (float) Payment::firstOrFail()->amount);
        Http::assertNothingSent();
    }

    public function test_ghana_postal_code_is_optional(): void
    {
        $product = $this->product();
        $this->actingAs($this->customer())->withSession($this->cartWith([[$product, 1, null]]))
            ->post(route('checkout.store'), $this->checkoutFields(['postal_code' => '']))
            ->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_international_customer_gets_a_dhl_estimate(): void
    {
        $product = $this->product(['price' => 100]);
        Http::fake(['express.api.dhl.com/*' => Http::response($this->dhlResponse(450))]);

        $response = $this->actingAs($this->customer())->withHeader('CF-IPCountry', 'GB')
            ->withSession($this->cartWith([[$product, 2, null]]))
            ->postJson(route('checkout.shipping-quote'), ['country' => 'GB', 'city' => 'London', 'postal_code' => 'SW1A 1AA'])
            ->assertOk()
            ->assertJsonPath('method', Order::SHIPPING_DHL)
            ->assertJsonPath('shipping', 450);

        // Cheapest product chosen, shown in USD (450 × 0.08)
        $this->assertSame('EXPRESS WORLDWIDE', $response->json('quote.service'));
        $this->assertSame('$36.00', $response->json('formatted.shipping'));
        $this->assertSame('GH₵660.00', $response->json('formatted.total_ghs')); // 200 + 10 + 450
    }

    public function test_dhl_request_uses_weight_and_dimensions_of_every_unit(): void
    {
        $a = $this->product(['weight_kg' => 0.5, 'length_cm' => 40, 'width_cm' => 30, 'height_cm' => 4]);
        $b = $this->product(['weight_kg' => 0.2, 'length_cm' => 25, 'width_cm' => 35, 'height_cm' => 2]);
        Http::fake(['express.api.dhl.com/*' => Http::response($this->dhlResponse())]);

        $this->actingAs($this->customer())->withSession($this->cartWith([[$a, 2, null], [$b, 3, null]]))
            ->postJson(route('checkout.shipping-quote'), ['country' => 'US', 'city' => 'New York', 'postal_code' => '10001'])
            ->assertOk();

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), '/rates')) {
                return false;
            }
            $package = $request['packages'][0];

            // 2 × 0.5 + 3 × 0.2 = 1.6 kg + 0.2 kg packaging
            return $request->method() === 'POST'
                && str_contains($request->url(), 'mydhlapi/test/rates')
                && $request->hasHeader('Authorization', 'Basic ' . base64_encode('key:secret'))
                && count($request['packages']) === 1
                && abs($package['weight'] - 1.8) < 0.0001
                && $package['dimensions'] === ['length' => 42.0, 'width' => 37.0, 'height' => 16.0] // max L, max W, stacked H (+2 cm padding)
                && $request['customerDetails']['receiverDetails']['countryCode'] === 'US'
                && $request['customerDetails']['shipperDetails']['countryCode'] === 'GH'
                && $request['accounts'][0]['number'] === '123456789';
        });
    }

    public function test_parcel_builder_splits_tall_stacks_and_reports_volumetric_weight(): void
    {
        config(['shipping.parcel.max_package_height_cm' => 10]);
        $product = $this->product(['weight_kg' => 0.1, 'length_cm' => 50, 'width_cm' => 40, 'height_cm' => 4]);

        $parcel = app(ParcelBuilder::class)->build([['product_id' => $product->id, 'quantity' => 5]]);

        $this->assertCount(3, $parcel['packages']); // 8 cm + 8 cm + 4 cm stacks
        $this->assertEquals(52 * 42 * 10 / 5000, $parcel['packages'][0]['length'] * $parcel['packages'][0]['width'] * $parcel['packages'][0]['height'] / 5000);
        $this->assertGreaterThan($parcel['actual_weight'], $parcel['volumetric_weight']); // light but bulky: DHL bills volume
        $this->assertSame($parcel['chargeable_weight'], max($parcel['chargeable_weight'], $parcel['actual_weight']));
    }

    public function test_international_order_adds_dhl_shipping_exactly_once(): void
    {
        $product = $this->product(['price' => 100]);
        Http::fake([
            'express.api.dhl.com/*' => Http::response($this->dhlResponse(450)),
            'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x']]),
        ]);
        $user = $this->customer();
        $session = $this->cartWith([[$product, 1, null]]);
        $address = ['country' => 'CA', 'city' => 'Toronto', 'postal_code' => 'M5V 2T6'];

        // Quote first (as the checkout page does), then place the order: DHL is asked only once
        $this->actingAs($user)->withSession($session)->postJson(route('checkout.shipping-quote'), $address)->assertOk();
        $this->actingAs($user)->post(route('checkout.store'), $this->checkoutFields($address))->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame(Order::SHIPPING_DHL, $order->shipping_method);
        $this->assertEquals(450, (float) $order->shipping_cost);
        $this->assertEquals(450, (float) $order->shipping_estimate);
        $this->assertNull($order->shipping_final_cost);
        $this->assertEquals(100 + 5 + 450, (float) $order->total_amount);
        $this->assertSame('EXPRESS WORLDWIDE', $order->shipping_quote['service']);
        $this->assertCount(1, Http::recorded(fn (Request $r) => str_contains($r->url(), 'dhl.com'))); // one DHL quote, reused

        // Paystack is asked for the GHS total in pesewas, in GHS
        $this->actingAs($user)->post(route('payment.initiate', $order))->assertRedirect('https://checkout.paystack.com/x');
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'paystack') && $r['amount'] === 55500 && $r['currency'] === 'GHS');
    }

    public function test_quote_in_another_currency_is_converted_to_cedis(): void
    {
        $product = $this->product();
        Http::fake(['express.api.dhl.com/*' => Http::response($this->dhlResponse(40, 'USD'))]);

        $this->actingAs($this->customer())->withSession($this->cartWith([[$product, 1, null]]))
            ->postJson(route('checkout.shipping-quote'), ['country' => 'US', 'city' => 'Austin', 'postal_code' => '73301'])
            ->assertOk()->assertJsonPath('shipping', 500); // 40 USD ÷ 0.08
    }

    public function test_dhl_errors_are_handled_and_block_the_order(): void
    {
        $product = $this->product();
        Http::fake(['express.api.dhl.com/*' => Http::response(['title' => 'Bad request', 'detail' => 'Invalid postal code', 'status' => 400], 400)]);
        $user = $this->customer();
        $session = $this->cartWith([[$product, 1, null]]);
        $address = ['country' => 'US', 'city' => 'Nowhere', 'postal_code' => '00000'];

        $this->actingAs($user)->withSession($session)->postJson(route('checkout.shipping-quote'), $address)
            ->assertStatus(422)->assertJsonFragment(['message' => 'DHL couldn\'t quote this address. Please check the country, city and postal code. (Invalid postal code)']);

        $this->actingAs($user)->withSession($session)->post(route('checkout.store'), $this->checkoutFields($address))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame(0, Order::count());
        $this->assertEquals(10, $product->fresh()->stock_quantity);
    }

    public function test_dhl_not_configured_shows_a_friendly_message(): void
    {
        config(['shipping.dhl.api_key' => null]);
        $product = $this->product();

        $this->actingAs($this->customer())->withSession($this->cartWith([[$product, 1, null]]))
            ->postJson(route('checkout.shipping-quote'), ['country' => 'US', 'city' => 'Austin', 'postal_code' => '73301'])
            ->assertStatus(422)->assertJsonFragment(['message' => 'International shipping rates are temporarily unavailable. Please contact us to complete your order.']);
    }

    public function test_international_checkout_shows_the_dhl_explanation(): void
    {
        $product = $this->product();

        $this->actingAs($this->customer(['country' => 'US']))->withSession($this->cartWith([[$product, 1, null]]))
            ->get(route('checkout.index'))
            ->assertOk()->assertSee(self::DHL_NOTICE)->assertSee('Calculated by DHL');
    }

    public function test_admin_records_final_dhl_charge_separately(): void
    {
        $product = $this->product();
        Http::fake(['express.api.dhl.com/*' => Http::response($this->dhlResponse(450))]);
        $this->actingAs($this->customer())->withSession($this->cartWith([[$product, 1, null]]))
            ->post(route('checkout.store'), $this->checkoutFields(['country' => 'US', 'city' => 'Austin', 'postal_code' => '73301']));
        $order = Order::firstOrFail();

        $this->actingAs($this->admin())->post(route('admin.orders.shipping', $order), ['shipping_final_cost' => 470])->assertRedirect();

        $order->refresh();
        $this->assertEquals(450, (float) $order->shipping_estimate);
        $this->assertEquals(450, (float) $order->shipping_cost);
        $this->assertEquals(470, (float) $order->shipping_final_cost);
        $this->assertEquals(105 + 450, (float) $order->total_amount); // customer total unchanged
        $this->assertNotNull($order->shipping_confirmed_at);
    }
}
