<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class InvoiceTest extends TestCase
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

    private function order(bool $paid = true): array
    {
        $product = $this->product(['name' => 'Rose Chiffon', 'price' => 100]);
        $user = $this->customer(['name' => 'Amina Mensah']);
        $this->actingAs($user)->withSession($this->cartWith([[$product, 2, null]]))
            ->post(route('checkout.store'), $this->checkoutFields())->assertRedirect();
        $order = Order::firstOrFail();
        if ($paid) {
            $order->update(['payment_status' => 'paid', 'paid_at' => now(), 'payment_method' => 'paystack', 'transaction_id' => 'NSR-1-ABC']);
        }

        return [$order->fresh(), $user];
    }

    public function test_customer_can_view_and_print_their_receipt(): void
    {
        [$order, $user] = $this->order();

        $this->actingAs($user)->get(route('orders.invoice', $order))
            ->assertOk()
            ->assertSee('Receipt')
            ->assertSee($order->invoiceNumber())
            ->assertSee('Amina Mensah')
            ->assertSee('Rose Chiffon')
            ->assertSee('GHS 210.00')        // 200 + 5% tax
            ->assertSee('Paid on delivery')  // Ghana shipping
            ->assertSee('NSR-1-ABC')
            ->assertSee('window.print()', false)
            ->assertSee(route('orders.invoice.download', $order), false);
    }

    public function test_customer_can_download_a_pdf_receipt(): void
    {
        [$order, $user] = $this->order();

        $response = $this->actingAs($user)->get(route('orders.invoice.download', $order))->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString($order->invoiceNumber() . '.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_receipt_is_only_available_once_paid(): void
    {
        [$order, $user] = $this->order(paid: false);

        $this->actingAs($user)->get(route('orders.invoice', $order))->assertNotFound();
        $this->actingAs($user)->get(route('orders.invoice.download', $order))->assertNotFound();
    }

    public function test_customers_cannot_see_other_customers_receipts(): void
    {
        [$order] = $this->order();

        $this->actingAs($this->customer())->get(route('orders.invoice', $order))->assertForbidden();
        $this->actingAs($this->customer())->get(route('orders.invoice.download', $order))->assertForbidden();
        auth()->logout();
        $this->get(route('orders.invoice', $order))->assertRedirect(route('login'));
    }

    public function test_admin_can_print_and_download_any_order_invoice(): void
    {
        [$order] = $this->order(paid: false);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.orders.invoice', $order))->assertOk()->assertSee('Invoice')->assertSee('Unpaid');
        $response = $this->actingAs($admin)->get(route('admin.orders.invoice.download', $order))->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertSee(route('admin.orders.invoice.download', $order), false);
        $this->actingAs($admin)->get(route('admin.orders.index'))->assertSee(route('admin.orders.invoice.download', $order), false);
    }

    public function test_admin_without_orders_access_cannot_get_invoices(): void
    {
        [$order] = $this->order();
        $admin = $this->admin();
        $admin->update(['is_super_admin' => false, 'admin_permissions' => ['products']]);

        $this->actingAs($admin)->get(route('admin.orders.invoice.download', $order))->assertRedirect(route('admin.products.index'));
    }

    public function test_receipt_buttons_appear_after_payment(): void
    {
        [$order, $user] = $this->order();

        $this->actingAs($user)->get(route('checkout.confirmation', $order))->assertSee(route('orders.invoice.download', $order), false);
        $this->actingAs($user)->get(route('orders.show', $order))->assertSee(route('orders.invoice', $order), false);
        $this->actingAs($user)->get(route('orders.index'))->assertSee(route('orders.invoice.download', $order), false);
    }
}
