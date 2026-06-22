<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * Show checkout form.
     */
    public function index()
    {
        $this->middleware('auth');

        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $shipping = 5.00;
        $tax = $subtotal * 0.05;
        $total = $subtotal + $shipping + $tax;

        $user = auth()->user();

        return view('checkout.index', compact('cart', 'subtotal', 'shipping', 'tax', 'total', 'user'));
    }

    /**
     * Process checkout and create order.
     */
    public function store(Request $request)
    {
        $this->middleware('auth');

        $request->validate([
            'shipping_address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
        ]);

        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $shipping = 5.00;
        $tax = $subtotal * 0.05;
        $total = $subtotal + $shipping + $tax;

        // Create order
        $order = Order::create([
            'user_id' => auth()->id(),
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'tax' => $tax,
            'total_amount' => $total,
            'shipping_address' => $request->shipping_address . ', ' . $request->city . ' ' . $request->postal_code,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        // Create order items
        foreach ($cart as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $item['price'] * $item['quantity'],
            ]);

            // Reduce stock
            $product = Product::find($item['product_id']);
            $product->decrement('stock_quantity', $item['quantity']);
        }

        // Create payment record
        Payment::create([
            'order_id' => $order->id,
            'amount' => $total,
            'status' => 'pending',
            'currency' => 'GHS',
        ]);

        // Clear cart
        session()->forget('cart');

        // Redirect to payment
        return redirect()->route('payment.show', $order->id);
    }

    /**
     * Show order confirmation.
     */
    public function confirmation(Order $order)
    {
        if (auth()->id() !== $order->user_id && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $order->load('items.product');

        return view('checkout.confirmation', compact('order'));
    }
}
