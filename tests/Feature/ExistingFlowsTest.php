<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

/**
 * Regression checks: existing storefront, auth, cart, payment and admin flows still work.
 */
class ExistingFlowsTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Http::preventStrayRequests();
        $this->fakeRates();
        Mail::fake();
    }

    public function test_auth_pages_and_registration_work(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();

        $user = $this->customer(['email' => 'login@example.com']);
        $this->post(route('login.post'), ['email' => 'login@example.com', 'password' => 'password123'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_checkout_requires_sign_in(): void
    {
        $this->get(route('checkout.index'))->assertRedirect(route('login'));
    }

    public function test_cart_add_update_and_remove(): void
    {
        $product = $this->product(['stock_quantity' => 3]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])->assertOk()->assertJson(['success' => true]);
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 5])->assertStatus(422);
        $this->post(route('cart.update'), ['product_id' => $product->id, 'quantity' => 3])->assertRedirect(route('cart.index'));
        $this->get(route('cart.index'))->assertOk()->assertSee($product->name);
        $this->post(route('cart.remove'), ['product_id' => $product->id])->assertRedirect();
        $this->assertEmpty(session('cart'));
    }

    public function test_full_paystack_payment_flow_still_confirms_orders(): void
    {
        $product = $this->product(['price' => 100]);
        $user = $this->customer();
        $this->actingAs($user)->withSession($this->cartWith([[$product, 1, null]]))
            ->post(route('checkout.store'), $this->checkoutFields())->assertRedirect();
        $order = Order::firstOrFail();

        $this->actingAs($user)->get(route('payment.show', $order))->assertOk()->assertSee('Pay GH₵105.00 with Paystack', false);

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc']]),
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 10500, 'currency' => 'GHS', 'fees' => 158]]),
        ]);
        $this->actingAs($user)->post(route('payment.initiate', $order))->assertRedirect('https://checkout.paystack.com/abc');
        $reference = Payment::whereNotNull('external_reference')->value('external_reference');

        $this->get(route('payment.callback', ['reference' => $reference]))->assertRedirect(route('checkout.confirmation', $order));

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->actingAs($user)->get(route('checkout.confirmation', $order))->assertOk()->assertSee('Pay on delivery');
        $this->actingAs($user)->get(route('orders.show', $order))->assertOk()->assertSee('GH₵105.00', false);
        $this->actingAs($user)->get(route('orders.index'))->assertOk();
    }

    public function test_payment_in_the_wrong_currency_is_not_accepted(): void
    {
        $product = $this->product(['price' => 100]);
        $user = $this->customer();
        $this->actingAs($user)->withSession($this->cartWith([[$product, 1, null]]))->post(route('checkout.store'), $this->checkoutFields());
        $order = Order::firstOrFail();
        $payment = Payment::create(['order_id' => $order->id, 'external_reference' => 'NSR-X', 'amount' => 105, 'status' => 'pending', 'currency' => 'GHS']);
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 10500, 'currency' => 'USD']])]);

        $this->get(route('payment.callback', ['reference' => $payment->external_reference]));

        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_cancelled_orders_cannot_be_paid(): void
    {
        $product = $this->product();
        $user = $this->customer();
        $this->actingAs($user)->withSession($this->cartWith([[$product, 1, null]]))->post(route('checkout.store'), $this->checkoutFields());
        $order = Order::firstOrFail();
        $order->update(['status' => 'cancelled']);

        $this->actingAs($user)->post(route('payment.initiate', $order))->assertRedirect(route('orders.show', $order));
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'paystack'));
    }

    public function test_admin_pages_render(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $this->actingAs($this->customer())->withSession($this->cartWith([[$product, 1, null]]))->post(route('checkout.store'), $this->checkoutFields());
        $order = Order::firstOrFail();
        $order->update(['payment_status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($admin);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Profit &amp; inventory', false)->assertSee('Gross profit');
        $this->get(route('admin.dashboard', ['range' => '30d', 'category_id' => $product->category_id]))->assertOk();
        $this->get(route('admin.inventory.index'))->assertOk()->assertSee($product->sku);
        $this->get(route('admin.inventory.movements'))->assertOk()->assertSee('Sold');
        $this->get(route('admin.inventory.returns.index'))->assertOk();
        $this->get(route('admin.inventory.import'))->assertOk();
        $this->get(route('admin.shipping.index'))->assertOk()->assertSee('Connected');
        $this->get(route('admin.orders.index'))->assertOk();
        $this->get(route('admin.orders.show', $order))->assertOk()->assertSee('Record a return');
        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.products.create'))->assertOk()->assertSee('Purchase cost');
        $this->get(route('admin.products.edit', $product))->assertOk()->assertSee($product->sku);
    }

    public function test_admin_can_create_a_product_with_inventory_fields(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'name' => 'New Satin', 'category_id' => $category->id, 'description' => 'Satin', 'price' => 150,
            'stock_quantity' => 12, 'cost_price' => 60, 'low_stock_threshold' => 3,
            'weight_kg' => 0.2, 'length_cm' => 28, 'width_cm' => 22, 'height_cm' => 2,
        ])->assertRedirect(route('admin.products.index'));

        $product = \App\Models\Product::where('name', 'New Satin')->firstOrFail();
        $this->assertMatchesRegularExpression('/^NSR-\d{5}$/', $product->sku);
        $this->assertEquals(60, (float) $product->cost_price);
        $this->assertSame(3, $product->low_stock_threshold);
        $opening = \App\Models\InventoryMovement::where('product_id', $product->id)->sole();
        $this->assertSame('opening', $opening->type);
        $this->assertSame(12, $opening->quantity_change);
    }

    public function test_dhl_test_quote_from_admin(): void
    {
        $product = $this->product();
        Http::fake(['express.api.dhl.com/*' => Http::response($this->dhlResponse(380))]);

        $this->actingAs($this->admin())->post(route('admin.shipping.test'), ['country' => 'US', 'city' => 'Austin', 'postal_code' => '73301', 'product_id' => $product->id, 'quantity' => 2])
            ->assertRedirect()->assertSessionHas('dhl_test.ok', true);
    }
}
