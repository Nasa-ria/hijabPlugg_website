<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductReturn;
use App\Services\InventoryReport;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class InventoryTest extends TestCase
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

    /**
     * Place a Ghana order through the real checkout and return it.
     */
    private function placeOrder(array $lines, ?\App\Models\User $user = null): Order
    {
        $user ??= $this->customer();
        $this->actingAs($user)->withSession($this->cartWith($lines))
            ->post(route('checkout.store'), $this->checkoutFields())->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    private function pay(Order $order, int $feesPesewas = 150): void
    {
        $payment = Payment::create(['order_id' => $order->id, 'gateway' => 'paystack', 'external_reference' => 'NSR-' . $order->id . '-T', 'amount' => $order->total_amount, 'status' => 'pending', 'currency' => 'GHS']);
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => [
            'status' => 'success', 'amount' => (int) round($order->total_amount * 100), 'currency' => 'GHS', 'fees' => $feesPesewas, 'reference' => $payment->external_reference,
        ]])]);

        $this->get(route('payment.callback', ['reference' => $payment->external_reference]))->assertRedirect(route('checkout.confirmation', $order));
    }

    public function test_checkout_reserves_stock_and_records_the_sale(): void
    {
        $product = $this->product(['stock_quantity' => 10, 'cost_price' => 35]);
        $user = $this->customer();

        $order = $this->placeOrder([[$product, 3, null]], $user);

        $this->assertEquals(7, $product->fresh()->stock_quantity);
        $movement = InventoryMovement::where('type', 'sale')->firstOrFail();
        $this->assertSame(-3, $movement->quantity_change);
        $this->assertSame(7, $movement->stock_after);
        $this->assertSame($order->id, $movement->order_id);
        $this->assertEquals(35, (float) $order->items->first()->unit_cost); // cost frozen at sale time
    }

    public function test_colour_stock_is_reduced_and_product_total_follows(): void
    {
        $product = $this->product(['stock_quantity' => 0]);
        $rose = $this->variant($product, ['stock_quantity' => 4, 'cost_price' => 30]);
        $this->variant($product, ['stock_quantity' => 6]);

        $this->placeOrder([[$product, 3, $rose]]);

        $this->assertEquals(1, $rose->fresh()->stock_quantity);
        $this->assertEquals(7, $product->fresh()->stock_quantity);
        $this->assertSame($rose->id, InventoryMovement::where('type', 'sale')->first()->variant_id);
    }

    public function test_cannot_oversell_when_stock_ran_out_after_adding_to_cart(): void
    {
        $product = $this->product(['stock_quantity' => 2]);
        $session = $this->cartWith([[$product, 2, null]]);
        $product->update(['stock_quantity' => 1]); // someone else bought one meanwhile

        $this->actingAs($this->customer())->withSession($session)
            ->post(route('checkout.store'), $this->checkoutFields())
            ->assertRedirect(route('cart.index'))->assertSessionHas('error');

        $this->assertSame(0, Order::count()); // whole order rolled back
        $this->assertEquals(1, $product->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::where('type', 'sale')->count());
    }

    public function test_paid_order_keeps_stock_deducted_and_records_paystack_fee(): void
    {
        $product = $this->product(['stock_quantity' => 5]);
        $order = $this->placeOrder([[$product, 2, null]]);

        $this->pay($order, 315);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals(3.15, (float) $order->payment_fees);
        $this->assertEquals(3, $product->fresh()->stock_quantity);
    }

    public function test_cancelling_before_dispatch_puts_stock_back_once(): void
    {
        $product = $this->product(['stock_quantity' => 5]);
        $order = $this->placeOrder([[$product, 2, null]]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.orders.update-status', $order), ['status' => 'cancelled'])->assertRedirect();
        $this->assertEquals(5, $product->fresh()->stock_quantity);

        // Releasing again does nothing
        $this->assertFalse(app(InventoryService::class)->releaseOrder($order->fresh(), 'again'));
        $this->assertEquals(5, $product->fresh()->stock_quantity);
        $release = InventoryMovement::where('type', 'sale_release')->sole();
        $this->assertSame($admin->id, $release->user_id);

        // Re-opening takes the stock again
        $this->actingAs($admin)->post(route('admin.orders.update-status', $order), ['status' => 'processing'])->assertRedirect();
        $this->assertEquals(3, $product->fresh()->stock_quantity);
    }

    public function test_unpaid_orders_release_their_stock_after_the_payment_window(): void
    {
        $product = $this->product(['stock_quantity' => 5]);
        $order = $this->placeOrder([[$product, 2, null]]);
        $order->forceFill(['created_at' => now()->subHours(49)])->save();

        $this->artisan('orders:release-unpaid')->assertSuccessful();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertEquals(5, $product->fresh()->stock_quantity);
    }

    public function test_late_payment_after_release_takes_the_stock_again(): void
    {
        $product = $this->product(['stock_quantity' => 5]);
        $order = $this->placeOrder([[$product, 2, null]]);
        app(InventoryService::class)->releaseOrder($order, 'expired');
        $this->assertEquals(5, $product->fresh()->stock_quantity);

        $this->pay($order->fresh());

        $this->assertEquals(3, $product->fresh()->stock_quantity);
        $this->assertNull($order->fresh()->stock_released_at);
    }

    public function test_resellable_return_goes_back_into_stock(): void
    {
        $product = $this->product(['stock_quantity' => 5, 'cost_price' => 40]);
        $order = $this->placeOrder([[$product, 2, null]]);
        $this->pay($order);
        $item = $order->items()->first();

        $this->actingAs($this->admin())->post(route('admin.inventory.returns.store'), [
            'order_item_id' => $item->id, 'quantity' => 1, 'condition' => 'resellable', 'reason' => 'Wrong colour',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertEquals(4, $product->fresh()->stock_quantity);
        $return = ProductReturn::sole();
        $this->assertEquals(100, (float) $return->refund_amount); // full price by default
        $this->assertSame(1, InventoryMovement::where('type', 'return_restock')->sole()->quantity_change);
    }

    public function test_damaged_return_is_not_put_back_into_sellable_stock(): void
    {
        $product = $this->product(['stock_quantity' => 5, 'cost_price' => 40]);
        $order = $this->placeOrder([[$product, 2, null]]);
        $this->pay($order);

        $this->actingAs($this->admin())->post(route('admin.inventory.returns.store'), [
            'order_item_id' => $order->items()->first()->id, 'quantity' => 2, 'condition' => 'damaged',
        ])->assertRedirect();

        $this->assertEquals(3, $product->fresh()->stock_quantity); // unchanged
        $movement = InventoryMovement::where('type', 'return_damaged')->sole();
        $this->assertSame(0, $movement->quantity_change);
        $this->assertSame(2, $movement->quantity);
        $this->assertEquals(80, ProductReturn::sole()->lossAmount());
    }

    public function test_cannot_return_more_than_was_bought_or_from_unpaid_orders(): void
    {
        $product = $this->product();
        $order = $this->placeOrder([[$product, 2, null]]);
        $item = $order->items()->first();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.inventory.returns.store'), ['order_item_id' => $item->id, 'quantity' => 1, 'condition' => 'resellable'])
            ->assertSessionHasErrors('quantity'); // unpaid

        $this->pay($order);
        $this->actingAs($admin)->post(route('admin.inventory.returns.store'), ['order_item_id' => $item->id, 'quantity' => 2, 'condition' => 'resellable'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.inventory.returns.store'), ['order_item_id' => $item->id, 'quantity' => 1, 'condition' => 'resellable'])
            ->assertSessionHasErrors('quantity');
        $this->assertSame(1, ProductReturn::count());
    }

    public function test_manual_adjustments_are_audited_and_cannot_go_negative(): void
    {
        $product = $this->product(['stock_quantity' => 3]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.inventory.adjust'), ['product_id' => $product->id, 'type' => 'restock', 'quantity' => 7, 'unit_cost' => 45, 'note' => 'Delivery INV-1'])->assertSessionHas('success');
        $this->assertEquals(10, $product->fresh()->stock_quantity);
        $this->assertEquals(45, (float) $product->fresh()->cost_price);

        $this->actingAs($admin)->post(route('admin.inventory.adjust'), ['product_id' => $product->id, 'type' => 'damage', 'quantity' => 2])->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.inventory.adjust'), ['product_id' => $product->id, 'type' => 'adjustment', 'mode' => 'set', 'quantity' => 6])->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.inventory.adjust'), ['product_id' => $product->id, 'type' => 'write_off', 'quantity' => 50])->assertSessionHas('error');

        $this->assertEquals(6, $product->fresh()->stock_quantity);
        $movements = InventoryMovement::where('product_id', $product->id)->orderBy('id')->get();
        $this->assertSame(['restock', 'damage', 'adjustment'], $movements->pluck('type')->all());
        $this->assertTrue($movements->every(fn ($m) => $m->user_id === $admin->id));
        $this->assertSame('Delivery INV-1', $movements[0]->note);
    }

    public function test_concurrent_reservation_cannot_take_stock_below_zero(): void
    {
        $product = $this->product(['stock_quantity' => 1]);
        $service = app(InventoryService::class);

        $service->adjust($product, null, -1, 'adjustment');

        $this->expectException(InsufficientStockException::class);
        $service->adjust($product->fresh(), null, -1, 'adjustment');
    }

    public function test_product_form_stock_edits_are_logged(): void
    {
        $product = $this->product(['stock_quantity' => 4]);
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => $product->name, 'category_id' => $product->category_id, 'description' => 'x',
            'price' => 100, 'stock_quantity' => 9, 'cost_price' => 41, 'is_available' => 1,
            'weight_kg' => 0.3, 'length_cm' => 30, 'width_cm' => 25, 'height_cm' => 3,
        ])->assertRedirect();

        $movement = InventoryMovement::where('product_id', $product->id)->sole();
        $this->assertSame('adjustment', $movement->type);
        $this->assertSame(5, $movement->quantity_change);
        $this->assertSame($admin->id, $movement->user_id);
        $this->assertEquals(41, (float) $product->fresh()->cost_price);
    }

    public function test_financial_report_separates_revenue_cost_profit_and_losses(): void
    {
        // 3 sold at ₵100 (cost ₵40); 1 returned resellable; 1 returned damaged; 1 damaged in store
        $product = $this->product(['stock_quantity' => 10, 'cost_price' => 40, 'price' => 100]);
        $order = $this->placeOrder([[$product, 3, null]]);
        $this->pay($order, 500);
        $item = $order->items()->first();
        $inventory = app(InventoryService::class);
        $inventory->recordReturn($item, 1, 'resellable');
        $inventory->recordReturn($item, 1, 'damaged', 60);
        $inventory->adjust($product->fresh(), null, -1, 'damage', 'Stained');

        $report = app(InventoryReport::class)->summary(now()->subDay(), now()->addDay());

        $this->assertEquals(300, $report['gross_sales']);
        $this->assertEquals(160, $report['refunds']);          // 100 + 60
        $this->assertEquals(140, $report['revenue']);          // 300 − 160
        $this->assertEquals(40, $report['cogs']);              // 3 × 40 − 2 returned × 40
        $this->assertEquals(100, $report['gross_profit']);     // revenue − COGS
        $this->assertEquals(80, $report['losses']);            // damaged return 40 + store damage 40
        $this->assertEquals(5, $report['payment_fees']);
        $this->assertEquals(15, $report['net_profit']);        // 100 − 80 − 5
        $this->assertSame(1, $report['units_sold']);
        $this->assertSame(2, $report['returned_units']);
        $this->assertEquals(15, (float) $report['tax_collected']); // never counted as revenue
        $this->assertEquals(0, (float) $report['shipping_collected']);

        $stock = app(InventoryReport::class)->stockPosition();
        $this->assertSame(7, $stock['units_in_stock']);          // 10 − 3 sold + 1 resold − 1 damaged
        $this->assertEquals(280, $stock['valuation']);           // 7 × 40
    }

    public function test_low_stock_items_are_flagged(): void
    {
        $this->product(['name' => 'Almost gone', 'stock_quantity' => 2, 'low_stock_threshold' => 3]);
        $this->product(['name' => 'Plenty', 'stock_quantity' => 50]);

        $low = app(InventoryReport::class)->stockPosition()['low_stock'];

        $this->assertSame(['Almost gone'], $low->pluck('name')->all());
        $this->actingAs($this->admin())->get(route('admin.inventory.index', ['stock' => 'low']))
            ->assertOk()->assertSee('Almost gone')->assertDontSee('Plenty');
    }
}
