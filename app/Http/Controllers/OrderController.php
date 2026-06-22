<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display customer's orders.
     */
    public function index()
    {
        $this->middleware('auth');

        $orders = auth()->user()->orders()
            ->with('items.product')
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    /**
     * Show order details.
     */
    public function show(Order $order)
    {
        $this->middleware('auth');

        if (auth()->id() !== $order->user_id && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $order->load('items.product', 'payment');

        return view('orders.show', compact('order'));
    }
}
