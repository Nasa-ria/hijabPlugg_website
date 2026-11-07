<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\InventoryService;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Paystack payments.
 *  1. initiate(): create a Paystack transaction and send the customer to Paystack's checkout.
 *  2. callback(): Paystack sends the customer back here; we verify the payment with Paystack.
 *  3. webhook():  Paystack also tells us directly (covers customers who close the tab early).
 * Keys live in .env (PAYSTACK_PUBLIC_KEY / PAYSTACK_SECRET_KEY), read via config/payment.php.
 */
class PaymentController extends Controller
{
    private const API = 'https://api.paystack.co';

    public function show(Order $order)
    {
        $this->authorizeOrder($order);

        if ($order->payment_status === 'paid') {
            return redirect()->route('checkout.confirmation', $order)->with('success', 'This order is already paid.');
        }

        $order->load('items.product', 'items.variant');

        return view('payment.show', [
            'order' => $order,
            'paystackReady' => (bool) config('payment.paystack_secret_key'),
        ]);
    }

    /**
     * Start a Paystack payment and redirect the customer to Paystack.
     */
    public function initiate(Request $request, Order $order)
    {
        $this->authorizeOrder($order);

        if ($order->payment_status === 'paid') {
            return redirect()->route('checkout.confirmation', $order)->with('success', 'This order is already paid.');
        }

        if ($order->status === 'cancelled') {
            return redirect()->route('orders.show', $order)->with('error', 'This order was cancelled, so it cannot be paid. Please place a new order.');
        }

        $secret = config('payment.paystack_secret_key');
        if (! $secret) {
            Log::error('Paystack secret key is missing (PAYSTACK_SECRET_KEY in .env).');

            return back()->with('error', 'Online payment is not available right now. Please try again shortly or contact us.');
        }

        $reference = 'NSR-' . $order->id . '-' . Str::upper(Str::random(10));

        try {
            $response = Http::withToken($secret)->timeout(20)->acceptJson()->post(self::API . '/transaction/initialize', [
                'email' => $order->contactEmail(),
                'amount' => (int) round($order->total_amount * 100), // pesewas
                'currency' => $order->currency ?: config('shop.base_currency', 'GHS'), // the amount is GHS, so the currency must be too
                'reference' => $reference,
                'callback_url' => route('payment.callback'),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'custom_fields' => [
                        ['display_name' => 'Order', 'variable_name' => 'order_number', 'value' => $order->order_number],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Paystack initialize failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);

            return back()->with('error', 'We couldn\'t reach Paystack. Please check your connection and try again.');
        }

        if (! $response->successful() || ! $response->json('status')) {
            Log::error('Paystack initialize rejected', ['order' => $order->order_number, 'response' => $response->json()]);

            return back()->with('error', 'Paystack couldn\'t start the payment: ' . ($response->json('message') ?: 'please try again.'));
        }

        Payment::create([
            'order_id' => $order->id,
            'gateway' => 'paystack',
            'external_reference' => $reference,
            'amount' => $order->total_amount,
            'status' => 'pending',
            'currency' => $order->currency ?: config('shop.base_currency', 'GHS'), // the amount is GHS, so the currency must be too
        ]);
        $order->update(['payment_method' => 'paystack']);

        return redirect()->away($response->json('data.authorization_url'));
    }

    /**
     * Customer returns from Paystack: verify the payment, then show the confirmation.
     */
    public function callback(Request $request)
    {
        $reference = (string) $request->query('reference', $request->query('trxref', ''));
        $payment = $reference !== '' ? Payment::with('order')->where('external_reference', $reference)->first() : null;

        if (! $payment) {
            return redirect()->route('home')->with('error', 'We couldn\'t match that payment to an order. If you were charged, please contact us with your order number.');
        }

        $result = $this->verify($payment);

        return match ($result) {
            'paid' => redirect()->route('checkout.confirmation', $payment->order)->with('success', 'Payment successful! Your order is confirmed and a confirmation email is on its way.'),
            'pending' => redirect()->route('payment.show', $payment->order)->with('error', 'Your payment is still being processed. Please wait a moment and refresh, or try again.'),
            default => redirect()->route('payment.failed', $payment->order),
        };
    }

    /**
     * Paystack webhook (charge.success). Signed with your secret key; see Paystack dashboard → Settings → API Keys & Webhooks.
     */
    public function webhook(Request $request)
    {
        $secret = (string) config('payment.paystack_secret_key');
        $signature = (string) $request->header('x-paystack-signature');

        if ($secret === '' || ! hash_equals(hash_hmac('sha512', $request->getContent(), $secret), $signature)) {
            return response('Invalid signature', 401);
        }

        if ($request->input('event') === 'charge.success') {
            $payment = Payment::with('order')->where('external_reference', (string) $request->input('data.reference'))->first();
            if ($payment) {
                $this->verify($payment);
            }
        }

        return response('OK', 200);
    }

    public function failed(Order $order)
    {
        $this->authorizeOrder($order);

        return view('payment.failed', compact('order'));
    }

    /**
     * Ask Paystack for the real result and update the order. Returns paid | pending | failed.
     * Safe to call more than once: a paid order is only confirmed (and emailed) once.
     */
    private function verify(Payment $payment): string
    {
        $order = $payment->order;
        if ($order->payment_status === 'paid') {
            return 'paid';
        }

        try {
            $response = Http::withToken((string) config('payment.paystack_secret_key'))->timeout(20)->acceptJson()
                ->get(self::API . '/transaction/verify/' . rawurlencode($payment->external_reference));
        } catch (\Throwable $e) {
            Log::error('Paystack verify failed', ['reference' => $payment->external_reference, 'error' => $e->getMessage()]);

            return 'pending';
        }

        $data = $response->json('data') ?? [];
        $status = $data['status'] ?? null;
        $expected = (int) round($order->total_amount * 100);

        $currencyMatches = strtoupper((string) ($data['currency'] ?? $order->currency)) === strtoupper((string) ($order->currency ?: 'GHS'));

        if ($response->successful() && $status === 'success' && $currencyMatches && (int) ($data['amount'] ?? 0) >= $expected) {
            $payment->update(['status' => 'success', 'processed_at' => now(), 'metadata' => $data]);
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => 'paystack',
                'transaction_id' => $payment->external_reference,
                'paid_at' => now(),
                // Paystack's fee (pesewas), tracked apart from revenue
                'payment_fees' => round(((int) ($data['fees'] ?? 0)) / 100, 2),
            ]);

            // Paid after the unpaid-order hold expired and its stock was released: take it again
            if ($order->stock_released_at) {
                $this->reReserveStock($order);
            }

            // Confirms the order, emails the customer and the store (each only once)
            app(OrderStatusService::class)->confirmPaidOrder($order);

            return 'paid';
        }

        if ($status === 'success') {
            // Paid less than the order total: never confirm automatically
            Log::warning('Paystack amount mismatch', ['order' => $order->order_number, 'paid' => $data['amount'] ?? null, 'expected' => $expected]);
        }

        if (in_array($status, ['ongoing', 'pending', 'processing', 'queued'], true)) {
            return 'pending';
        }

        $payment->update(['status' => 'failed', 'error_message' => $data['gateway_response'] ?? $response->json('message'), 'metadata' => $data]);

        return 'failed';
    }

    private function reReserveStock(Order $order): void
    {
        $shortfalls = app(InventoryService::class)->deductForOrder($order, allowShortfall: true);

        if ($shortfalls) {
            $missing = collect($shortfalls)->map(fn ($s) => $s['missing'] . ' × ' . ($s['item']->product?->name ?? 'item') . ($s['item']->color_name ? ' (' . $s['item']->color_name . ')' : ''))->implode(', ');
            $order->update(['notes' => trim($order->notes . "\n[Stock] Paid after the order expired; not enough stock for: $missing. Please contact the customer.")]);
            Log::warning('Late payment with insufficient stock', ['order' => $order->order_number, 'missing' => $missing]);
        }
    }

    private function authorizeOrder(Order $order): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->id === $order->user_id || $user->isAdmin()), 403);
    }
}
