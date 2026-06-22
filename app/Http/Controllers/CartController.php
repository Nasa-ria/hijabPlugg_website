<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display the shopping cart.
     */
    public function index()
    {
        $cart = session('cart', []);
        $total = 0;
        $subtotal = 0;
        $shipping = 5.00;

        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $tax = $subtotal * 0.05; // 5% tax
        $total = $subtotal + $shipping + $tax;

        return view('cart.index', compact('cart', 'subtotal', 'shipping', 'tax', 'total'));
    }

    /**
     * Add a product to cart.
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $product = \App\Models\Product::findOrFail($request->product_id);

        if ($product->stock_quantity < $request->quantity) {
            return back()->with('error', 'Insufficient stock available.');
        }

        $cart = session('cart', []);
        $key = 'product_' . $product->id;

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $request->quantity;
        } else {
            $cart[$key] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->sale_price ?? $product->price,
                'image' => $product->main_image,
                'quantity' => $request->quantity,
            ];
        }

        session(['cart' => $cart]);

        return redirect()->route('cart.index')->with('success', 'Product added to cart!');
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0|max:100',
        ]);

        $cart = session('cart', []);
        $key = 'product_' . $request->product_id;

        if ($request->quantity <= 0) {
            unset($cart[$key]);
        } else {
            $product = \App\Models\Product::findOrFail($request->product_id);

            if ($product->stock_quantity < $request->quantity) {
                return back()->with('error', 'Insufficient stock available.');
            }

            if (isset($cart[$key])) {
                $cart[$key]['quantity'] = $request->quantity;
            }
        }

        session(['cart' => $cart]);

        return redirect()->route('cart.index')->with('success', 'Cart updated!');
    }

    /**
     * Remove item from cart.
     */
    public function remove(Request $request)
    {
        $cart = session('cart', []);
        $key = 'product_' . $request->product_id;
        unset($cart[$key]);

        session(['cart' => $cart]);

        return redirect()->route('cart.index')->with('success', 'Product removed from cart!');
    }

    /**
     * Clear the entire cart.
     */
    public function clear()
    {
        session()->forget('cart');

        return redirect()->route('cart.index')->with('success', 'Cart cleared!');
    }
}
