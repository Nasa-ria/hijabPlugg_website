<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Exceptions\InsufficientStockException;
use App\Services\InventoryService;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private OrderStatusService $statuses, private InventoryService $inventory)
    {
    }

    /**
     * All orders, with search (number, name, email) and status/date filters.
     */
    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => ['nullable', Rule::in(array_keys(Order::STATUSES))],
            'payment_status' => 'nullable|in:unpaid,paid,failed',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $orders = Order::query()
            ->with('user')
            ->withCount('items')
            ->withSum('items as units', 'quantity')
            ->when($request->q, function ($query, $q) use ($like) {
                $term = '%' . trim($q) . '%';
                $query->where(fn ($w) => $w
                    ->where('order_number', $like, $term)
                    ->orWhere('customer_name', $like, $term)
                    ->orWhere('customer_email', $like, $term));
            })
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->payment_status, fn ($q, $s) => $q->where('payment_status', $s))
            ->when($request->from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'items.variant', 'items.returns', 'user', 'payment', 'statusHistory.changedBy', 'returns.processedBy', 'returns.product', 'inventoryMovements.user']);

        return view('admin.orders.show', [
            'order' => $order,
            'emailedStatuses' => $order->statusHistory->where('email_status', 'sent')->pluck('status')->unique()->values(),
        ]);
    }

    /**
     * Change the order status. The customer is emailed unless the admin chose to skip it.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'notify' => 'nullable|boolean',
        ]);

        if ($data['status'] === $order->status) {
            return back()->with('error', 'The order is already ' . $order->status_label . '.');
        }

        $previous = $order->status;

        // Re-opening a cancelled order: its stock must be taken again first
        if ($previous === 'cancelled' && $order->stock_released_at) {
            try {
                $this->inventory->deductForOrder($order, by: $request->user());
            } catch (InsufficientStockException $e) {
                return back()->with('error', 'The order can\'t be re-opened: ' . $e->getMessage());
            }
        }

        $entry = $this->statuses->changeStatus($order, $data['status'], $request->boolean('notify'), $request->user());

        $message = 'Status changed to ' . $entry->status_label . '.';

        // Cancelled before it left the store: the items go back into stock.
        // (Once out for delivery, items come back through Returns instead.)
        if ($data['status'] === 'cancelled' && in_array($previous, ['processing', 'packaged'], true)
            && $this->inventory->releaseOrder($order, 'Order ' . $order->order_number . ' cancelled', $request->user())) {
            $message .= ' Its items are back in stock.';
        }
        if ($entry->email_status === 'failed') {
            return back()->with('error', $message . ' But the email to the customer failed: ' . $entry->email_error . ' You can resend it from the timeline.');
        }

        return back()->with('success', $message . match ($entry->email_status) {
            'sent' => ' The customer has been emailed.',
            default => $request->boolean('notify') ? ' No email sent: the customer was already emailed about this status.' : ' No email was sent.',
        });
    }

    /**
     * Retry an email that failed (never sends twice for the same status).
     */
    public function resendEmail(Order $order, OrderStatusHistory $history)
    {
        abort_unless($history->order_id === $order->id, 404);

        if ($this->statuses->alreadyEmailed($order, $history->status)) {
            return back()->with('error', 'The customer has already been emailed about "' . $history->status_label . '".');
        }

        return $this->statuses->email($history)
            ? back()->with('success', 'Email sent to ' . $order->contactEmail() . '.')
            : back()->with('error', 'The email failed again: ' . $history->fresh()->email_error);
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'required|in:unpaid,paid,failed',
        ]);

        $becamePaid = $request->payment_status === 'paid' && $order->payment_status !== 'paid';

        $order->update([
            'payment_status' => $request->payment_status,
            'paid_at' => $request->payment_status === 'paid' ? ($order->paid_at ?? now()) : null,
        ]);

        // Marking an order paid by hand (e.g. mobile money) confirms it like a card payment would
        if ($becamePaid) {
            if ($order->stock_released_at) {
                $this->inventory->deductForOrder($order, allowShortfall: true, by: $request->user());
            }
            $entry = $this->statuses->confirmPaidOrder($order);

            return $entry->email_status === 'failed'
                ? back()->with('error', 'Marked as paid, but the confirmation email failed: ' . $entry->email_error . ' You can resend it from the timeline.')
                : back()->with('success', 'Marked as paid' . ($entry->email_status === 'sent' ? ' and the order confirmation was emailed to the customer.' : '.'));
        }

        return back()->with('success', 'Payment status updated!');
    }

    /**
     * Record the confirmed DHL charge once the parcel has shipped. Kept apart from the
     * estimate the customer paid, so the shipping margin can be reported.
     */
    public function updateShipping(Request $request, Order $order)
    {
        $data = $request->validate([
            'shipping_final_cost' => 'nullable|numeric|min:0|max:1000000',
        ]);

        $final = $data['shipping_final_cost'] ?? null;
        $order->update([
            'shipping_final_cost' => $final,
            'shipping_confirmed_at' => $final === null ? null : now(),
        ]);

        return back()->with('success', $final === null ? 'Final shipping cost cleared.' : 'Final shipping cost saved.');
    }
}
