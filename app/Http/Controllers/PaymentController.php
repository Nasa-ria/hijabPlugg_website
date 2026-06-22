<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Show payment page.
     */
    public function show(Order $order)
    {
        if (auth()->id() !== $order->user_id && !auth()->user()->isAdmin()) {
            abort(403);
        }

        if ($order->payment_status === 'paid') {
            return redirect()->route('checkout.confirmation', $order)->with('success', 'Order already paid!');
        }

        $order->load('items.product');

        return view('payment.show', compact('order'));
    }

    /**
     * Initiate Paystack payment.
     */
    public function initiate(Request $request, Order $order)
    {
        if (auth()->id() !== $order->user_id) {
            abort(403);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['error' => 'Order already paid'], 400);
        }

        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.paystack.co/transaction/initialize',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . config('payment.paystack_secret_key'),
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'email' => auth()->user()->email,
                    'amount' => $order->total_amount * 100, // Paystack expects amount in cents
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'user_id' => auth()->id(),
                    ],
                    'callback_url' => route('payment.callback'),
                ]),
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);

            if ($error) {
                return response()->json(['error' => 'Payment initialization failed'], 400);
            }

            $responseData = json_decode($response, true);

            if ($responseData['status']) {
                // Store payment reference
                Payment::create([
                    'order_id' => $order->id,
                    'external_reference' => $responseData['data']['reference'],
                    'amount' => $order->total_amount,
                    'status' => 'pending',
                    'currency' => 'GHS',
                ]);

                return response()->json([
                    'authorization_url' => $responseData['data']['authorization_url'],
                ]);
            } else {
                return response()->json(['error' => 'Payment initialization failed'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle payment callback from Paystack.
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect('/')->with('error', 'Invalid payment reference.');
        }

        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.paystack.co/transaction/verify/' . htmlspecialchars($reference),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . config('payment.paystack_secret_key'),
                ],
            ]);

            $response = curl_exec($curl);
            curl_close($curl);

            $responseData = json_decode($response, true);

            if (!$responseData['status']) {
                return redirect('/')->with('error', 'Payment verification failed.');
            }

            $data = $responseData['data'];

            if ($data['status'] === 'success') {
                $payment = Payment::where('external_reference', $reference)->first();

                if (!$payment) {
                    return redirect('/')->with('error', 'Payment record not found.');
                }

                $order = $payment->order;

                // Update order and payment status
                $order->update([
                    'payment_status' => 'paid',
                    'transaction_id' => $reference,
                    'status' => 'processing',
                    'paid_at' => now(),
                ]);

                $payment->update([
                    'status' => 'success',
                    'processed_at' => now(),
                    'metadata' => $data,
                ]);

                return redirect()->route('checkout.confirmation', $order)->with('success', 'Payment successful! Your order has been confirmed.');
            } else {
                $payment = Payment::where('external_reference', $reference)->first();
                if ($payment) {
                    $payment->update(['status' => 'failed']);
                }

                return redirect('/')->with('error', 'Payment failed. Please try again.');
            }
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment verification error: ' . $e->getMessage());
        }
    }

    /**
     * Handle failed payment.
     */
    public function failed(Order $order)
    {
        if (auth()->id() !== $order->user_id && !auth()->user()->isAdmin()) {
            abort(403);
        }

        return view('payment.failed', compact('order'));
    }
}
