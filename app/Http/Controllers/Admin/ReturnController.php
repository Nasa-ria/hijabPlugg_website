<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\ProductReturn;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'condition' => ['nullable', Rule::in(array_keys(ProductReturn::CONDITIONS))],
        ]);

        $returns = ProductReturn::with(['order', 'product', 'variant', 'processedBy'])
            ->when($data['condition'] ?? null, fn ($q, $c) => $q->where('item_condition', $c))
            ->latest('processed_at')->latest('id')
            ->paginate(30)->withQueryString();

        return view('admin.inventory.returns', [
            'returns' => $returns,
            'totals' => [
                'units' => (int) ProductReturn::sum('quantity'),
                'refunded' => (float) ProductReturn::sum('refund_amount'),
                'damaged' => (int) ProductReturn::where('item_condition', 'damaged')->sum('quantity'),
                'loss' => (float) ProductReturn::where('item_condition', 'damaged')->selectRaw('COALESCE(SUM(quantity * unit_cost), 0) as loss')->value('loss'),
            ],
        ]);
    }

    /**
     * Record a return from the order page.
     */
    public function store(Request $request, InventoryService $inventory)
    {
        $data = $request->validate([
            'order_item_id' => 'required|integer|exists:order_items,id',
            'quantity' => 'required|integer|min:1|max:1000',
            'condition' => ['required', Rule::in(array_keys(ProductReturn::CONDITIONS))],
            'refund_amount' => 'nullable|numeric|min:0|max:1000000',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $item = OrderItem::findOrFail($data['order_item_id']);
        $return = $inventory->recordReturn(
            $item,
            (int) $data['quantity'],
            $data['condition'],
            isset($data['refund_amount']) && $data['refund_amount'] !== '' ? (float) $data['refund_amount'] : null,
            $data['reason'] ?? null,
            $data['notes'] ?? null,
            $request->user(),
        );

        return back()->with('success', $return->isDamaged()
            ? "Return recorded: {$return->quantity} damaged unit(s) written off (not put back into stock)."
            : "Return recorded: {$return->quantity} unit(s) put back into stock.");
    }
}
