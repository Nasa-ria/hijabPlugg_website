<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryExcel;
use App\Services\InventoryReport;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function __construct(private InventoryReport $report)
    {
    }

    /**
     * Stock list with valuation, low-stock alerts and quick adjustments.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:categories,id',
            'stock' => 'nullable|in:low,out,in',
        ]);

        $filters = ['category_id' => $data['category_id'] ?? null];
        $units = $this->report->units($filters)
            ->when($data['q'] ?? null, function ($units, $q) {
                $q = mb_strtolower($q);

                return $units->filter(fn ($u) => str_contains(mb_strtolower($u->name . ' ' . $u->colour . ' ' . $u->sku), $q));
            })
            ->when($data['stock'] ?? null, fn ($units, $stock) => $units->filter(fn ($u) => match ($stock) {
                'low' => $u->is_low,
                'out' => $u->is_out,
                default => $u->stock > 0,
            }))
            ->sortBy([['is_out', 'desc'], ['is_low', 'desc'], ['name', 'asc']])
            ->values();

        return view('admin.inventory.index', [
            'units' => $units,
            'position' => $this->report->stockPosition($filters),
            'categories' => Category::orderBy('name')->get(),
            'manualTypes' => array_intersect_key(InventoryMovement::TYPES, array_flip(InventoryMovement::MANUAL_TYPES)),
        ]);
    }

    /**
     * Stock history (audit trail).
     */
    public function movements(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'nullable|integer',
            'type' => ['nullable', Rule::in(array_keys(InventoryMovement::TYPES))],
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $movements = InventoryMovement::with(['product', 'variant', 'order', 'user'])
            ->when($data['product_id'] ?? null, fn ($q, $id) => $q->where('product_id', $id))
            ->when($data['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($data['from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', Carbon::parse($d)->startOfDay()))
            ->when($data['to'] ?? null, fn ($q, $d) => $q->where('created_at', '<=', Carbon::parse($d)->endOfDay()))
            ->latest('created_at')->latest('id')
            ->paginate(40)->withQueryString();

        return view('admin.inventory.movements', [
            'movements' => $movements,
            'products' => Product::withTrashed()->orderBy('name')->get(['id', 'name']),
            'types' => InventoryMovement::TYPES,
        ]);
    }

    /**
     * Add stock, correct a count, or record damage / a write-off.
     */
    public function adjust(Request $request, InventoryService $inventory)
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer',
            'type' => ['required', Rule::in(InventoryMovement::MANUAL_TYPES)],
            'mode' => 'nullable|in:change,set',
            'quantity' => 'required|integer|min:0|max:100000',
            'direction' => 'nullable|in:in,out',
            'unit_cost' => 'nullable|numeric|min:0|max:1000000',
            'note' => 'nullable|string|max:200',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $variant = ! empty($data['variant_id']) ? ProductVariant::where('product_id', $product->id)->findOrFail($data['variant_id']) : null;
        $quantity = (int) $data['quantity'];
        $note = $data['note'] ?? null;

        try {
            if (($data['mode'] ?? 'change') === 'set' && $data['type'] === 'adjustment') {
                $movement = $inventory->setStock($product, $variant, $quantity, 'adjustment', $note ?: 'Stock count', $request->user());
            } else {
                if ($quantity < 1) {
                    return back()->withErrors(['quantity' => 'Enter how many units.']);
                }
                $change = match ($data['type']) {
                    'restock' => $quantity,
                    'damage', 'write_off' => -$quantity,
                    default => ($data['direction'] ?? 'in') === 'out' ? -$quantity : $quantity,
                };

                if ($data['type'] === 'restock' && isset($data['unit_cost'])) {
                    // A delivery at a new price updates the purchase cost going forward
                    ($variant ?? $product)->update(['cost_price' => $data['unit_cost']]);
                }

                $movement = $inventory->adjust($product, $variant, $change, $data['type'], $note, $request->user(), isset($data['unit_cost']) ? (float) $data['unit_cost'] : null);
            }
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $name = $product->name . ($variant ? ' (' . $variant->color_name . ')' : '');

        return back()->with('success', $movement
            ? "{$movement->type_label}: {$name} now has {$movement->stock_after} in stock."
            : "{$name} already had that count; nothing changed.");
    }

    /**
     * Download inventory and sales as Excel.
     */
    public function export(Request $request, InventoryExcel $excel)
    {
        [$from, $to, $filters] = $this->reportFilters($request);

        $path = $excel->export($from, $to, $filters);
        $name = 'nasaari-inventory-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.xlsx';

        return response()->download($path, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    public function importForm()
    {
        return view('admin.inventory.import', [
            'categories' => Category::orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function template(InventoryExcel $excel)
    {
        return response()->download($excel->template(), 'nasaari-stock-update-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    public function import(Request $request, InventoryExcel $excel)
    {
        $request->validate([
            // The content is checked by actually reading the workbook (XlsxReader); MIME sniffing
            // is unreliable for .xlsx files saved by Google Sheets or LibreOffice
            'file' => 'required|file|max:5120|extensions:xlsx',
        ], [
            'file.extensions' => 'Please upload an Excel .xlsx file (File → Save As → Excel Workbook).',
        ]);

        $result = $excel->import($request->file('file')->getRealPath(), $request->user());

        if ($result['errors']) {
            return back()->with('import_errors', $result['errors'])
                ->with('error', 'Nothing was imported. Please fix the ' . count($result['errors']) . ' problem(s) below and upload again.');
        }

        return redirect()->route('admin.inventory.index')->with('success', "Import complete: {$result['applied']} item(s) updated" . ($result['unchanged'] ? ", {$result['unchanged']} already correct." : '.'));
    }

    /**
     * Shared date-range / category / product filters for reports and exports.
     */
    public static function reportFilters(Request $request): array
    {
        $data = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'category_id' => 'nullable|integer|exists:categories,id',
            'product_id' => 'nullable|integer|exists:products,id',
        ]);

        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->subDays(29)->startOfDay();
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : now()->endOfDay();

        return [$from, $to, [
            'category_id' => $data['category_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
        ]];
    }
}
