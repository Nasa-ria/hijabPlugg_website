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
    public function index(\App\Services\CheckoutCalculator $calculator, \App\Services\CurrencyService $currency)
    {
        $cart = session('cart', []);

        // Shipping depends on the delivery country, chosen at checkout. Until then the
        // visitor's own country decides which shipping note to show.
        $country = \App\Support\Countries::code(auth()->user()?->country) ?? $currency->country() ?? config('shipping.domestic_country', 'GH');
        $totals = $calculator->totals($cart, $country);
        $domestic = $calculator->isDomestic($country);

        return view('cart.index', [
            'cart' => $cart,
            'totals' => $totals,
            'domestic' => $domestic,
            'subtotal' => $totals['subtotal'],
            'shipping' => $totals['shipping'],
            'tax' => $totals['tax'],
            'total' => $totals['total'],
        ]);
    }

    /**
     * Add a product (and optionally one of its colours) to the cart.
     * Returns JSON for the shop popup, or redirects for regular forms.
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|integer',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $product = \App\Models\Product::with('variants')->findOrFail($request->product_id);
        $variant = null;

        if ($product->variants->isNotEmpty()) {
            $variant = $product->variants->firstWhere('id', (int) $request->variant_id);

            if (! $variant) {
                return $this->cartError($request, 'Please choose a colour.');
            }
        }

        $key = $this->cartKey($product->id, $variant?->id);
        $cart = session('cart', []);
        $inCart = $cart[$key]['quantity'] ?? 0;
        $available = $variant ? $variant->stock_quantity : $product->stock_quantity;

        if (! $product->is_available || $available < $inCart + $request->quantity) {
            $message = $available - $inCart > 0
                ? 'Only ' . ($available - $inCart) . ' more available in this colour.'
                : 'Sorry, this is out of stock.';

            return $this->cartError($request, $variant ? $message : str_replace(' in this colour', '', $message));
        }

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $request->quantity;
        } else {
            $cart[$key] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'color_name' => $variant?->color_name,
                'name' => $product->name,
                'price' => $variant ? $variant->effectivePrice() : $product->currentPrice(),
                'image' => $variant ? $variant->imagePath() : $product->main_image,
                'quantity' => $request->quantity,
            ];
        }

        session(['cart' => $cart]);

        \App\Support\Analytics::record($request, 'add_to_cart', ['product_id' => $product->id, 'variant_id' => $variant?->id, 'source' => in_array($request->input('source'), ['popup', 'page', 'home'], true) ? $request->input('source') : null]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Added to your cart.',
                'cart_count' => count($cart),
            ]);
        }

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
        $key = $this->requestKey($request);

        if ($request->quantity <= 0) {
            unset($cart[$key]);
        } elseif (isset($cart[$key])) {
            $variantId = $cart[$key]['variant_id'] ?? null;
            $available = $variantId
                ? (\App\Models\ProductVariant::find($variantId)?->stock_quantity ?? 0)
                : \App\Models\Product::findOrFail($request->product_id)->stock_quantity;

            if ($available < $request->quantity) {
                return back()->with('error', 'Insufficient stock available.');
            }

            $cart[$key]['quantity'] = $request->quantity;
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
        unset($cart[$this->requestKey($request)]);

        session(['cart' => $cart]);

        return redirect()->route('cart.index')->with('success', 'Product removed from cart!');
    }

    /**
     * Cart lines are keyed by product and colour, so two colours of one veil are separate lines.
     */
    private function cartKey(int $productId, ?int $variantId): string
    {
        return 'product_' . $productId . ($variantId ? '_variant_' . $variantId : '');
    }

    /**
     * Forms on the cart page send the line key; older forms only send product_id.
     */
    private function requestKey(Request $request): string
    {
        $key = (string) $request->input('key');

        return $key !== '' && array_key_exists($key, session('cart', []))
            ? $key
            : 'product_' . $request->product_id;
    }

    private function cartError(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return back()->with('error', $message);
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
