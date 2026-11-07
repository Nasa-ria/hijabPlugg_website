<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Services\InventoryService;
use App\Services\OrderStatusService;
use App\Support\Xlsx\XlsxReader;
use App\Support\Xlsx\XlsxWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class InventoryExcelTest extends TestCase
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

    private function xlsx(array $headers, array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx') . '.xlsx';
        (new XlsxWriter())->addSheet('Stock update', $headers, $rows)->save($path);

        return new UploadedFile($path, 'stock.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function sheet($response, int $index = 0): array
    {
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive();
        $zip->open($path);
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();
        $this->assertContains('xl/worksheets/sheet' . ($index + 1) . '.xml', $names);

        return (new XlsxReader())->read($path);
    }

    public function test_export_contains_accurate_inventory_and_financial_columns(): void
    {
        $category = $this->category('Chiffon');
        $product = $this->product(['name' => 'Rose Chiffon', 'category' => $category, 'stock_quantity' => 0, 'price' => 100, 'cost_price' => 40]);
        $this->product(['name' => 'Silk Wrap', 'stock_quantity' => 4]);
        $admin = $this->admin();
        app(InventoryService::class)->setStock($product, null, 10, 'opening', 'Initial stock', $admin); // as the product form does
        app(InventoryService::class)->adjust($product, null, 5, 'restock', 'Delivery', $admin);

        // Sell 3, return 1 damaged
        $order = Order::create(['user_id' => $this->customer()->id, 'subtotal' => 300, 'shipping_cost' => 0, 'tax' => 15, 'total_amount' => 315, 'shipping_address' => 'Accra', 'status' => 'processing', 'payment_status' => 'unpaid']);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 3, 'price' => 100, 'subtotal' => 300]);
        app(InventoryService::class)->deductForOrder($order);
        $order->update(['payment_status' => 'paid', 'paid_at' => now()]);
        app(InventoryService::class)->recordReturn($order->items()->first(), 1, 'damaged', 100, null, null, $admin);

        $response = $this->actingAs($admin)->get(route('admin.inventory.export', [
            'from' => now()->subDay()->toDateString(), 'to' => now()->toDateString(), 'category_id' => $category->id,
        ]));

        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $rows = $this->sheet($response);
        $header = $rows[0];
        $this->assertSame(['Product name', 'SKU', 'Product ID', 'Category', 'Opening stock', 'Stock added', 'Adjustments', 'Quantity sold', 'Quantity returned', 'Damaged quantity', 'Remaining stock', 'Purchase cost (GHS)', 'Selling price (GHS)', 'Total sales (GHS)', 'Total cost (GHS)', 'Profit (GHS)', 'Loss (GHS)', 'Date'], $header);

        $row = array_combine($header, array_pad($rows[1], count($header), null));
        $this->assertSame('Rose Chiffon', $row['Product name']);
        $this->assertSame($product->sku, $row['SKU']);
        $this->assertSame(10, $row['Opening stock']);
        $this->assertSame(5, $row['Stock added']);
        $this->assertSame(3, $row['Quantity sold']);
        $this->assertSame(1, $row['Quantity returned']);
        $this->assertSame(1, $row['Damaged quantity']);
        $this->assertSame(12, $row['Remaining stock']);       // 10 + 5 − 3, damaged not restocked
        $this->assertEquals(200, $row['Total sales (GHS)']);  // 300 − 100 refund
        $this->assertEquals(80, $row['Total cost (GHS)']);    // 3 × 40 − 40 returned
        $this->assertEquals(120, $row['Profit (GHS)']);
        $this->assertEquals(40, $row['Loss (GHS)']);
        $this->assertNotContains('Silk Wrap', array_column($rows, 0)); // category filter applied
        $this->assertSame('TOTAL', end($rows)[0]);
    }

    public function test_import_adds_and_sets_stock_and_updates_costs(): void
    {
        $a = $this->product(['stock_quantity' => 5, 'cost_price' => 10]);
        $b = $this->product(['stock_quantity' => 8]);
        $colourProduct = $this->product(['stock_quantity' => 0]);
        $colour = $this->variant($colourProduct, ['stock_quantity' => 2]);
        $admin = $this->admin();

        $file = $this->xlsx(['SKU', 'Product', 'Action', 'Quantity', 'Purchase cost', 'Note'], [
            [$a->sku, 'A', 'add', 10, 12.5, 'Delivery 7'],
            [$b->sku, 'B', 'set', 3, null, 'Stock take'],
            [$colour->sku, 'Colour', '', 4, null, null],
        ]);

        $this->actingAs($admin)->post(route('admin.inventory.import.store'), ['file' => $file])
            ->assertRedirect(route('admin.inventory.index'))->assertSessionHas('success');

        $this->assertEquals(15, $a->fresh()->stock_quantity);
        $this->assertEquals(12.5, (float) $a->fresh()->cost_price);
        $this->assertEquals(3, $b->fresh()->stock_quantity);
        $this->assertEquals(6, $colour->fresh()->stock_quantity);
        $this->assertEquals(6, $colourProduct->fresh()->stock_quantity);
        $this->assertSame(['import', 'adjustment', 'import'], InventoryMovement::orderBy('id')->pluck('type')->all());
        $this->assertTrue(InventoryMovement::all()->every(fn ($m) => $m->user_id === $admin->id));
    }

    public function test_import_with_any_invalid_row_changes_nothing(): void
    {
        $a = $this->product(['stock_quantity' => 5]);
        $withColours = $this->product();
        $this->variant($withColours);

        $file = $this->xlsx(['SKU', 'Action', 'Quantity'], [
            [$a->sku, 'add', 10],            // valid
            [$a->sku, 'add', 1],             // duplicate SKU
            ['NOPE-1', 'add', 1],            // unknown
            [$withColours->sku, 'add', 2],   // must use a colour SKU
            [$a->sku . 'x', 'add', 1],       // unknown
            [$this->product()->sku, 'add', -4], // invalid quantity
            [$this->product()->sku, 'add', 2.5], // not whole
            [$this->product()->sku, 'remove', 2], // bad action
        ]);

        $this->actingAs($this->admin())->post(route('admin.inventory.import.store'), ['file' => $file])
            ->assertRedirect()->assertSessionHas('import_errors');

        $errors = session('import_errors');
        $this->assertCount(7, $errors);
        $this->assertStringContainsString('already on row 2', $errors[0]);
        $this->assertEquals(5, $a->fresh()->stock_quantity); // nothing applied
        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_import_rejects_files_that_are_not_xlsx(): void
    {
        $file = UploadedFile::fake()->createWithContent('stock.csv', "SKU,Quantity\nA,1");

        $this->actingAs($this->admin())->post(route('admin.inventory.import.store'), ['file' => $file])
            ->assertSessionHasErrors('file');
    }

    public function test_template_lists_every_sku(): void
    {
        $product = $this->product();

        $response = $this->actingAs($this->admin())->get(route('admin.inventory.import.template'))->assertOk();
        $rows = $this->sheet($response);

        $this->assertSame(['SKU', 'Product', 'Action', 'Quantity', 'Purchase cost', 'Note'], $rows[0]);
        $this->assertSame($product->sku, $rows[1][0]);
        $this->assertSame('set', $rows[1][2]);
    }

    public function test_only_admins_with_inventory_access_can_export(): void
    {
        $this->actingAs($this->customer())->get(route('admin.inventory.export'))->assertRedirect('/');

        $limited = $this->admin();
        $limited->update(['is_super_admin' => false, 'admin_permissions' => ['orders']]);
        $this->actingAs($limited)->get(route('admin.inventory.export'))->assertRedirect(route('admin.orders.index'));
    }
}
