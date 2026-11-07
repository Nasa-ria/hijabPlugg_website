<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * "Track your order": customers look up an order with its number and their email.
 * No account needed; both must match, and lookups are rate limited (see routes).
 */
class OrderTrackingController extends Controller
{
    public function show(Request $request)
    {
        return view('orders.track', ['orderNumber' => $request->query('order', '')]);
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'order_number' => 'required|string|max:60',
            'email' => 'required|email|max:255',
        ]);

        $order = Order::with(['items.product', 'items.variant', 'statusHistory', 'user'])
            ->where('order_number', trim($data['order_number']))
            ->first();

        $matches = $order && strcasecmp((string) $order->contactEmail(), trim($data['email'])) === 0;

        if (! $matches) {
            return back()->withInput()->withErrors([
                'order_number' => 'We couldn\'t find an order with those details. Please check your order number and email.',
            ]);
        }

        return view('orders.track', ['orderNumber' => $order->order_number, 'order' => $order]);
    }
}
