<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Shipping\DhlRateService;

/**
 * One place for cart and checkout totals, so the cart page, the checkout page and the
 * order that is saved can never disagree. All amounts are GHS.
 *
 *  - Ghana: shipping is paid to the courier on delivery, so it is 0 here.
 *  - Elsewhere: the DHL quote, which is added to the total exactly once.
 *  - Tax applies to the product subtotal only.
 */
class CheckoutCalculator
{
    public const QUOTE_SESSION = 'shipping_quote';

    public function __construct(private DhlRateService $dhl)
    {
    }

    public function isDomestic(?string $country): bool
    {
        return strtoupper((string) $country) === strtoupper(config('shipping.domestic_country', 'GH'));
    }

    public function subtotal(array $cart): float
    {
        return round(array_sum(array_map(fn ($item) => (float) $item['price'] * (int) $item['quantity'], $cart)), 2);
    }

    /**
     * @return array{subtotal: float, tax: float, tax_rate: float, shipping: float, total: float, shipping_method: ?string, shipping_pending: bool}
     */
    public function totals(array $cart, ?string $country, ?array $quote = null): array
    {
        $subtotal = $this->subtotal($cart);
        $rate = (float) config('shop.tax_rate', 0.05);
        $tax = round($subtotal * $rate, 2);

        if ($country === null) {
            $method = null;
            $shipping = 0.0;
        } elseif ($this->isDomestic($country)) {
            $method = Order::SHIPPING_POD;
            $shipping = 0.0; // paid directly to the courier on delivery: never part of this payment
        } else {
            $method = Order::SHIPPING_DHL;
            $shipping = $quote ? round((float) $quote['amount'], 2) : 0.0;
        }

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'tax_rate' => $rate,
            'shipping' => $shipping,
            'total' => round($subtotal + $tax + $shipping, 2),
            'shipping_method' => $method,
            // International order without a DHL quote yet: the total is not final
            'shipping_pending' => $method === Order::SHIPPING_DHL && ! $quote,
        ];
    }

    /**
     * Cart lines in the shape the parcel builder needs.
     */
    public function lines(array $cart): array
    {
        return array_values(array_map(fn ($item) => [
            'product_id' => (int) $item['product_id'],
            'quantity' => (int) $item['quantity'],
        ], $cart));
    }

    /**
     * A quote is only valid for the exact cart and address it was calculated for.
     */
    public function fingerprint(array $cart, array $destination): string
    {
        $lines = array_map(fn ($i) => [(int) $i['product_id'], (int) ($i['variant_id'] ?? 0), (int) $i['quantity']], array_values($cart));
        sort($lines);

        return hash('sha256', json_encode([
            $lines,
            strtoupper(trim($destination['country'] ?? '')),
            mb_strtolower(trim($destination['city'] ?? '')),
            mb_strtoupper(preg_replace('/\s+/', '', (string) ($destination['postal_code'] ?? ''))),
        ]));
    }

    /**
     * The saved quote for this cart and address, if it hasn't expired.
     */
    public function savedQuote(array $cart, array $destination): ?array
    {
        $saved = session(self::QUOTE_SESSION);

        if (! is_array($saved) || ($saved['fingerprint'] ?? null) !== $this->fingerprint($cart, $destination)) {
            return null;
        }

        return now()->lt($saved['quote']['expires_at']) ? $saved['quote'] : null;
    }

    /**
     * Saved quote, or a fresh one from DHL (saved for later requests).
     *
     * @throws \App\Services\Shipping\ShippingQuoteException
     */
    public function quote(array $cart, array $destination): array
    {
        if ($quote = $this->savedQuote($cart, $destination)) {
            return $quote;
        }

        $quote = $this->dhl->quote($destination, $this->lines($cart), $this->subtotal($cart));
        session([self::QUOTE_SESSION => ['fingerprint' => $this->fingerprint($cart, $destination), 'quote' => $quote]]);

        return $quote;
    }

    public function forgetQuote(): void
    {
        session()->forget(self::QUOTE_SESSION);
    }
}
