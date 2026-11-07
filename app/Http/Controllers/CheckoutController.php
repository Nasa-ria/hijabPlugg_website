<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\CheckoutCalculator;
use App\Services\CurrencyService;
use App\Services\InventoryService;
use App\Services\Shipping\ShippingQuoteException;
use App\Support\Countries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutCalculator $calculator,
        private CurrencyService $currency,
        private InventoryService $inventory,
    ) {
    }

    /**
     * Show checkout form.
     */
    public function index()
    {
        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $user = auth()->user();
        $country = old('country') ?: (Countries::code($user->country) ?? $this->currency->country() ?? config('shipping.domestic_country', 'GH'));
        $destination = [
            'country' => $country,
            'city' => old('city', $user->city),
            'postal_code' => old('postal_code', $user->postal_code),
        ];
        $quote = $this->calculator->isDomestic($country) ? null : $this->calculator->savedQuote($cart, $destination);
        $totals = $this->calculator->totals($cart, $country, $quote);

        return view('checkout.index', [
            'cart' => $cart,
            'user' => $user,
            'country' => $country,
            'countries' => Countries::options(config('shipping.domestic_country', 'GH')),
            'quote' => $quote,
            'totals' => $totals,
            // Kept for older partials that read these directly
            'subtotal' => $totals['subtotal'],
            'shipping' => $totals['shipping'],
            'tax' => $totals['tax'],
            'total' => $totals['total'],
        ]);
    }

    /**
     * Live shipping estimate for the checkout page (JSON).
     * The amount is calculated here from the session cart; nothing the browser sends is trusted.
     */
    public function shippingQuote(Request $request)
    {
        $data = $request->validate($this->addressRules(withContact: false));
        $cart = session('cart', []);

        if (empty($cart)) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        $destination = $this->destination($data);
        $quote = null;

        if (! $this->calculator->isDomestic($destination['country'])) {
            try {
                $quote = $this->calculator->quote($cart, $destination);
            } catch (ShippingQuoteException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        $totals = $this->calculator->totals($cart, $destination['country'], $quote);
        $fmt = fn ($v) => $this->currency->format($v);

        return response()->json([
            'method' => $totals['shipping_method'],
            'shipping' => $totals['shipping'],
            'quote' => $quote ? [
                'service' => $quote['service'],
                'estimated_delivery' => $quote['estimated_delivery'] ? \Carbon\Carbon::parse($quote['estimated_delivery'])->format('D j M Y') : null,
                'chargeable_weight' => $quote['chargeable_weight'],
                'expires_at' => $quote['expires_at'],
            ] : null,
            'formatted' => [
                'subtotal' => $fmt($totals['subtotal']),
                'tax' => $fmt($totals['tax']),
                'shipping' => $quote ? $fmt($totals['shipping']) : null,
                'total' => $fmt($totals['total']),
                'total_ghs' => $this->currency->formatBase($totals['total']),
            ],
        ]);
    }

    /**
     * Process checkout and create order.
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->addressRules(withContact: true));

        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $destination = $this->destination($data);
        $domestic = $this->calculator->isDomestic($destination['country']);
        $quote = null;

        if (! $domestic) {
            // Re-uses the quote the customer saw, or asks DHL again if the cart/address changed
            try {
                $quote = $this->calculator->quote($cart, $destination);
            } catch (ShippingQuoteException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        $totals = $this->calculator->totals($cart, $destination['country'], $quote);
        $displayCurrency = $this->currency->current();

        try {
            $order = DB::transaction(function () use ($data, $cart, $destination, $totals, $quote, $displayCurrency) {
                $order = Order::create([
                    'user_id' => auth()->id(),
                    'customer_name' => auth()->user()->name,
                    'customer_email' => strtolower(trim($data['email'])),
                    'customer_phone' => $data['phone'],
                    // All amounts GHS (the source of truth)
                    'subtotal' => $totals['subtotal'],
                    'shipping_cost' => $totals['shipping'],
                    'tax' => $totals['tax'],
                    'total_amount' => $totals['total'],
                    'currency' => $this->currency->base(),
                    // What the customer was shown (display only)
                    'display_currency' => $displayCurrency,
                    'display_exchange_rate' => $this->currency->rate($displayCurrency),
                    'shipping_address' => trim($data['shipping_address'] . ', ' . $destination['city'] . ' ' . ($destination['postal_code'] ?? '') . ', ' . Countries::name($destination['country'])),
                    'shipping_method' => $totals['shipping_method'],
                    'shipping_country' => $destination['country'],
                    'shipping_city' => $destination['city'],
                    'shipping_postal_code' => $destination['postal_code'],
                    'shipping_estimate' => $quote['amount'] ?? null,
                    'shipping_quote' => $quote,
                    'status' => 'processing',
                    'payment_status' => 'unpaid',
                ]);

                foreach ($cart as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'color_name' => $item['color_name'] ?? null,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $item['price'] * $item['quantity'],
                    ]);
                }

                // Locks the stock rows and reserves the units; throws if anything sold out meanwhile
                $this->inventory->deductForOrder($order);

                Payment::create([
                    'order_id' => $order->id,
                    'amount' => $totals['total'],
                    'status' => 'pending',
                    'currency' => $this->currency->base(),
                ]);

                return $order;
            });
        } catch (InsufficientStockException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage() . ' Please update your cart and try again.');
        }

        session()->forget('cart');
        $this->calculator->forgetQuote();

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

    private function addressRules(bool $withContact): array
    {
        $domestic = config('shipping.domestic_country', 'GH');

        return array_merge($withContact ? [
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:20',
            'shipping_address' => 'required|string|max:255',
        ] : [
            'shipping_address' => 'nullable|string|max:255',
        ], [
            'country' => ['required', 'string', 'size:2', Rule::in(array_keys(Countries::LIST))],
            'city' => 'required|string|max:100',
            // DHL needs a postal code for most countries; Ghana deliveries don't
            'postal_code' => "nullable|required_unless:country,$domestic|string|max:20",
        ]);
    }

    private function destination(array $data): array
    {
        return [
            'country' => strtoupper($data['country']),
            'city' => trim($data['city']),
            'postal_code' => isset($data['postal_code']) ? trim($data['postal_code']) : null,
            'address' => isset($data['shipping_address']) ? trim($data['shipping_address']) : null,
        ];
    }
}
