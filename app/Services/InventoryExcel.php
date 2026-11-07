<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Xlsx\XlsxReader;
use App\Support\Xlsx\XlsxWriter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Excel export of inventory and sales, and bulk stock updates by Excel import.
 */
class InventoryExcel
{
    public const IMPORT_HEADERS = ['SKU', 'Product', 'Action', 'Quantity', 'Purchase cost', 'Note'];

    public function __construct(private InventoryReport $report)
    {
    }

    /**
     * Workbook with three sheets: per-item inventory & sales, a money summary, and the stock history.
     * Returns the path of a temporary file.
     */
    public function export(Carbon $from, Carbon $to, array $filters = []): string
    {
        $rows = $this->report->breakdown($from, $to, $filters);
        $summary = $this->report->summary($from, $to, $filters);
        $period = $from->format('Y-m-d') . ' – ' . $to->format('Y-m-d');

        $columns = [
            'Product name' => 'name', 'SKU' => 'sku', 'Product ID' => 'product_id', 'Category' => 'category',
            'Opening stock' => 'opening_stock', 'Stock added' => 'stock_added', 'Adjustments' => 'adjustments',
            'Quantity sold' => 'quantity_sold', 'Quantity returned' => 'quantity_returned', 'Damaged quantity' => 'damaged_quantity',
            'Remaining stock' => 'remaining_stock', 'Purchase cost (GHS)' => 'purchase_cost', 'Selling price (GHS)' => 'selling_price',
            'Total sales (GHS)' => 'total_sales', 'Total cost (GHS)' => 'total_cost', 'Profit (GHS)' => 'profit',
            'Loss (GHS)' => 'loss', 'Date' => 'period',
        ];
        $money = ['Purchase cost (GHS)', 'Selling price (GHS)', 'Total sales (GHS)', 'Total cost (GHS)', 'Profit (GHS)', 'Loss (GHS)'];
        $integers = ['Product ID', 'Opening stock', 'Stock added', 'Adjustments', 'Quantity sold', 'Quantity returned', 'Damaged quantity', 'Remaining stock'];

        $data = $rows->map(fn ($row) => array_map(fn ($field) => $row->$field, array_values($columns)))->all();
        // Totals row
        $sum = fn ($field) => $rows->sum($field);
        $data[] = ['TOTAL', null, null, null, $sum('opening_stock'), $sum('stock_added'), $sum('adjustments'), $sum('quantity_sold'),
            $sum('quantity_returned'), $sum('damaged_quantity'), $sum('remaining_stock'), null, null,
            round($sum('total_sales'), 2), round($sum('total_cost'), 2), round($sum('profit'), 2), round($sum('loss'), 2), $period];

        $formats = array_fill_keys($money, 'money') + array_fill_keys($integers, 'integer') + ['SKU' => 'text'];

        $xlsx = new XlsxWriter();
        $xlsx->addSheet('Inventory & Sales', array_keys($columns), $data, $formats, ['Product name' => 40, 'SKU' => 24, 'Category' => 18, 'Date' => 24]);
        $xlsx->addSheet('Summary', ['Measure', 'Value', 'Notes'], $this->summaryRows($summary, $period, $filters), ['Value' => 'decimal'], ['Measure' => 32, 'Value' => 16, 'Notes' => 70]);
        $xlsx->addSheet('Stock movements', ['Date', 'SKU', 'Product', 'Colour', 'Type', 'Change', 'Stock after', 'Unit cost (GHS)', 'Order', 'By', 'Note'],
            $this->movementRows($from, $to, $filters),
            ['Change' => 'integer', 'Stock after' => 'integer', 'Unit cost (GHS)' => 'money', 'SKU' => 'text'],
            ['Date' => 18, 'SKU' => 24, 'Product' => 32, 'Type' => 24, 'Note' => 50]);

        $path = $this->tempPath('inventory-export');
        $xlsx->save($path);

        return $path;
    }

    private function summaryRows(array $s, string $period, array $filters): array
    {
        $rows = [
            ['Period', $period, 'Sales count on the day paid; returns on the day processed.'],
            ['Currency', 'GHS', 'All amounts are Ghana Cedis, the store\'s accounting currency.'],
        ];
        if (! empty($filters['category_id']) || ! empty($filters['product_id'])) {
            $rows[] = ['Filter', 'Filtered', 'Shipping, tax and payment fees are per order and are left out of filtered reports.'];
        }

        $line = fn ($label, $value, $note = '') => [$label, $value === null ? null : (is_int($value) ? $value : round((float) $value, 2)), $note];

        return array_merge($rows, [
            $line('Paid orders', $s['orders']),
            $line('Items sold (after returns)', $s['units_sold']),
            $line('Gross sales', $s['gross_sales'], 'Products only; excludes shipping and tax.'),
            $line('Refunds', $s['refunds']),
            $line('Revenue', $s['revenue'], 'Gross sales − refunds.'),
            $line('Cost of goods sold', $s['cogs'], 'Units sold × purchase cost at time of sale, less cost of returned units.'),
            $line('Gross profit', $s['gross_profit'], 'Revenue − cost of goods sold.'),
            $line('Losses', $s['losses'], 'Damaged returns and recorded damage/write-offs, at purchase cost.'),
            $line('Payment fees', $s['order_level'] ? $s['payment_fees'] : null, 'Paystack fees.'),
            $line('Net profit', $s['net_profit'], 'Gross profit − losses − payment fees.'),
            $line('Returned items', $s['returned_units']),
            $line('Returned value (refunded)', $s['returned_value']),
            $line('Shipping collected (DHL)', $s['shipping_collected'], 'Charged to international customers; not revenue.'),
            $line('Shipping paid to DHL (confirmed)', $s['shipping_paid'], $s['shipping_unconfirmed'] ? $s['shipping_unconfirmed'] . ' order(s) still without a confirmed DHL charge.' : ''),
            $line('Tax collected', $s['tax_collected'], 'Not revenue.'),
        ]);
    }

    private function movementRows(Carbon $from, Carbon $to, array $filters): \Generator
    {
        $query = InventoryMovement::with(['product:id,name,sku', 'variant:id,color_name,sku', 'order:id,order_number', 'user:id,name'])
            ->whereBetween('created_at', [$from, $to])
            ->when($filters['product_id'] ?? null, fn ($q, $id) => $q->where('product_id', $id))
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->whereHas('product', fn ($p) => $p->withTrashed()->where('category_id', $id)))
            ->orderBy('created_at')->orderBy('id');

        foreach ($query->lazy(500) as $m) {
            yield [
                $m->created_at->format('Y-m-d H:i'),
                $m->variant?->sku ?: $m->product?->sku,
                $m->product?->name,
                $m->variant?->color_name,
                $m->type_label,
                $m->quantity_change,
                $m->stock_after,
                (float) $m->unit_cost,
                $m->order?->order_number,
                $m->user?->name ?? 'System',
                $m->note,
            ];
        }
    }

    /**
     * Import template prefilled with every SKU and its current stock.
     */
    public function template(): string
    {
        $rows = $this->report->units()->map(fn ($u) => [$u->sku, $u->name . ($u->colour ? ' – ' . $u->colour : ''), 'set', $u->stock, $u->cost, null])->all();

        $xlsx = new XlsxWriter();
        $xlsx->addSheet('Stock update', self::IMPORT_HEADERS, $rows, ['SKU' => 'text', 'Quantity' => 'integer', 'Purchase cost' => 'money'], ['SKU' => 24, 'Product' => 40, 'Note' => 40]);
        $xlsx->addSheet('How to use', ['Column', 'What to enter'], [
            ['SKU', 'Required. Must match a product or colour SKU exactly. Each SKU may appear only once.'],
            ['Product', 'For your reference only; not imported.'],
            ['Action', '"add" adds the quantity to the current stock (a delivery). "set" sets the stock to the quantity (a stock count). Empty = add.'],
            ['Quantity', 'Required whole number. "add": 1 or more. "set": 0 or more.'],
            ['Purchase cost', 'Optional. New purchase cost per unit in GHS.'],
            ['Note', 'Optional. Saved in the stock history.'],
            ['Validation', 'If any row has a problem, nothing is imported and every problem is listed.'],
        ], [], ['Column' => 16, 'What to enter' => 100]);

        $path = $this->tempPath('inventory-template');
        $xlsx->save($path);

        return $path;
    }

    /**
     * Validate the whole file first, then apply every row in one transaction (all or nothing).
     *
     * @return array{applied: int, unchanged: int, errors: string[]}
     */
    public function import(string $path, User $by): array
    {
        try {
            $rows = (new XlsxReader())->read($path, (int) config('shop.inventory.import_max_rows', 2000) + 1);
        } catch (\RuntimeException $e) {
            return ['applied' => 0, 'unchanged' => 0, 'errors' => [$e->getMessage()]];
        }

        // Rows are keyed by their spreadsheet row (0 = row 1), so messages point at the right row
        $headerKey = array_key_first($rows);
        $header = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $headerKey === null ? [] : $rows[$headerKey]);
        unset($rows[$headerKey]);
        $col = fn (string $name) => array_search($name, $header, true);
        $skuCol = $col('sku');
        $qtyCol = $col('quantity');
        $actionCol = $col('action');
        $costCol = $col('purchase cost');
        $noteCol = $col('note');

        if ($skuCol === false || $qtyCol === false) {
            return ['applied' => 0, 'unchanged' => 0, 'errors' => ['The first row must contain the column names, including "SKU" and "Quantity". Download the template to see the layout.']];
        }

        $errors = [];
        $planned = [];
        $seen = [];
        $products = Product::with('variants')->get();
        $bySku = [];
        foreach ($products as $product) {
            if ($product->sku) {
                $bySku[mb_strtoupper($product->sku)] = [$product, null];
            }
            foreach ($product->variants as $variant) {
                if ($variant->sku) {
                    $bySku[mb_strtoupper($variant->sku)] = [$product, $variant];
                }
            }
        }

        foreach ($rows as $index => $row) {
            $line = $index + 1; // spreadsheet row number
            $sku = trim((string) ($row[$skuCol] ?? ''));
            $qtyRaw = $row[$qtyCol] ?? null;

            if ($sku === '' && ($qtyRaw === null || $qtyRaw === '')) {
                continue; // blank line
            }

            $action = ($actionCol === false ? '' : mb_strtolower(trim((string) ($row[$actionCol] ?? '')))) ?: 'add';
            $key = mb_strtoupper($sku);

            if ($sku === '') {
                $errors[] = "Row $line: SKU is missing.";
                continue;
            }
            if (isset($seen[$key])) {
                $errors[] = "Row $line: SKU $sku is already on row {$seen[$key]}. Each SKU may appear only once.";
                continue;
            }
            $seen[$key] = $line;

            if (! isset($bySku[$key])) {
                $errors[] = "Row $line: no product or colour has SKU $sku.";
                continue;
            }
            [$product, $variant] = $bySku[$key];
            if (! $variant && $product->variants->isNotEmpty()) {
                $errors[] = "Row $line: {$product->name} has colours; use each colour's own SKU.";
                continue;
            }
            if (! in_array($action, ['add', 'set'], true)) {
                $errors[] = "Row $line: Action must be \"add\" or \"set\" (found \"$action\").";
                continue;
            }

            $quantity = filter_var(is_float($qtyRaw) && floor($qtyRaw) == $qtyRaw ? (int) $qtyRaw : $qtyRaw, FILTER_VALIDATE_INT);
            if ($quantity === false || $quantity < ($action === 'add' ? 1 : 0) || $quantity > 100000) {
                $errors[] = "Row $line: Quantity must be a whole number " . ($action === 'add' ? 'from 1' : 'from 0') . ' to 100000 (found "' . $qtyRaw . '").';
                continue;
            }

            $cost = null;
            if ($costCol !== false && ($row[$costCol] ?? null) !== null && $row[$costCol] !== '') {
                if (! is_numeric($row[$costCol]) || $row[$costCol] < 0 || $row[$costCol] > 1000000) {
                    $errors[] = "Row $line: Purchase cost must be a number of 0 or more.";
                    continue;
                }
                $cost = round((float) $row[$costCol], 2);
            }

            $planned[] = [
                'product' => $product,
                'variant' => $variant,
                'action' => $action,
                'quantity' => $quantity,
                'cost' => $cost,
                'note' => $noteCol !== false ? mb_substr(trim((string) ($row[$noteCol] ?? '')), 0, 200) : '',
            ];
        }

        if (empty($errors) && empty($planned)) {
            $errors[] = 'The file has no stock rows.';
        }
        if ($errors) {
            return ['applied' => 0, 'unchanged' => 0, 'errors' => $errors];
        }

        $inventory = app(InventoryService::class);
        $applied = $unchanged = 0;

        DB::transaction(function () use ($planned, $inventory, $by, &$applied, &$unchanged) {
            foreach ($planned as $p) {
                if ($p['cost'] !== null) {
                    ($p['variant'] ?? $p['product'])->update(['cost_price' => $p['cost']]);
                }

                $note = trim('Excel import' . ($p['note'] ? ': ' . $p['note'] : ''));
                $movement = $p['action'] === 'add'
                    ? $inventory->adjust($p['product'], $p['variant'], $p['quantity'], 'import', $note, $by, $p['cost'])
                    // A stock count is a correction, not a delivery: logged as an adjustment
                    : $inventory->setStock($p['product'], $p['variant'], $p['quantity'], 'adjustment', $note . ' (stock count)', $by);

                $movement || $p['cost'] !== null ? $applied++ : $unchanged++;
            }
        });

        return ['applied' => $applied, 'unchanged' => $unchanged, 'errors' => []];
    }

    private function tempPath(string $prefix): string
    {
        $dir = storage_path('app/tmp');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir . '/' . $prefix . '-' . bin2hex(random_bytes(6)) . '.xlsx';
    }
}
