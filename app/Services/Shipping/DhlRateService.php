<?php

namespace App\Services\Shipping;

use App\Services\CurrencyService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Live DHL Express rates (MyDHL API, POST /rates).
 * Credentials come from .env (DHL_API_KEY, DHL_API_SECRET, DHL_ACCOUNT_NUMBER).
 * The returned amount is converted to GHS, the store's accounting currency.
 */
class DhlRateService
{
    public function __construct(private ParcelBuilder $parcels, private CurrencyService $currency)
    {
    }

    public function isConfigured(): bool
    {
        $dhl = config('shipping.dhl');

        return filled($dhl['api_key']) && filled($dhl['api_secret']) && filled($dhl['account_number']);
    }

    public function baseUrl(): string
    {
        $dhl = config('shipping.dhl');

        return rtrim($dhl['urls'][$dhl['environment']] ?? $dhl['urls']['test'], '/');
    }

    /**
     * Request a rate.
     *
     * @param  array{country: string, city: string, postal_code?: ?string, address?: ?string}  $destination
     * @param  array<int, array{product_id:int, quantity:int}>  $lines
     * @param  float  $declaredValue  goods value in GHS (for customs)
     * @return array quote: amount (GHS), currency, provider_amount, provider_currency, service, product_code,
     *               estimated_delivery, transit_days, packages, weights, quoted_at, expires_at
     *
     * @throws ShippingQuoteException
     */
    public function quote(array $destination, array $lines, float $declaredValue): array
    {
        if (! $this->isConfigured()) {
            Log::error('DHL rate requested but DHL_API_KEY / DHL_API_SECRET / DHL_ACCOUNT_NUMBER are not set.');

            throw new ShippingQuoteException('International shipping rates are temporarily unavailable. Please contact us to complete your order.');
        }

        $parcel = $this->parcels->build($lines);
        if (empty($parcel['packages'])) {
            throw new ShippingQuoteException('Your cart is empty.');
        }

        $dhl = config('shipping.dhl');
        $origin = config('shipping.origin');
        $payload = [
            'customerDetails' => [
                'shipperDetails' => array_filter([
                    'postalCode' => (string) $origin['postal_code'],
                    'cityName' => $origin['city'],
                    'countryCode' => strtoupper($origin['country']),
                    'addressLine1' => $origin['address'] ?: null,
                ], fn ($v) => $v !== null),
                'receiverDetails' => array_filter([
                    'postalCode' => (string) ($destination['postal_code'] ?? ''),
                    'cityName' => $destination['city'],
                    'countryCode' => strtoupper($destination['country']),
                    'addressLine1' => ($destination['address'] ?? null) ?: null,
                ], fn ($v) => $v !== null),
            ],
            'accounts' => [['typeCode' => 'shipper', 'number' => (string) $dhl['account_number']]],
            'plannedShippingDateAndTime' => now()->addWeekdays(max(0, (int) $dhl['pickup_days']))->setTime(13, 0)->format('Y-m-d\TH:i:s\G\M\TP'),
            'unitOfMeasurement' => 'metric',
            'isCustomsDeclarable' => true,
            'monetaryAmount' => [['typeCode' => 'declaredValue', 'value' => round($declaredValue, 2), 'currency' => config('shop.base_currency', 'GHS')]],
            'packages' => array_map(fn ($p) => [
                'weight' => $p['weight'],
                'dimensions' => ['length' => $p['length'], 'width' => $p['width'], 'height' => $p['height']],
            ], $parcel['packages']),
            'nextBusinessDay' => true,
        ];
        if (filled($dhl['product_code'])) {
            $payload['productCode'] = $dhl['product_code'];
        }

        try {
            $response = Http::withBasicAuth($dhl['api_key'], $dhl['api_secret'])
                ->timeout((int) $dhl['timeout'])
                ->acceptJson()
                ->post($this->baseUrl() . '/rates', $payload);
        } catch (ConnectionException $e) {
            Log::error('DHL rates unreachable', ['error' => $e->getMessage()]);

            throw new ShippingQuoteException('We couldn\'t reach DHL to calculate shipping. Please try again in a moment.');
        }

        if (! $response->successful()) {
            $detail = (string) ($response->json('detail') ?? $response->json('title') ?? $response->body());
            Log::warning('DHL rates rejected', ['status' => $response->status(), 'detail' => mb_substr($detail, 0, 500), 'destination' => $destination['country']]);

            throw new ShippingQuoteException($response->status() < 500
                ? 'DHL couldn\'t quote this address. Please check the country, city and postal code. ' . ($this->customerSafeDetail($detail) ?? '')
                : 'DHL is not responding right now. Please try again in a moment.');
        }

        $product = $this->pickProduct((array) $response->json('products', []));
        if (! $product) {
            Log::warning('DHL returned no usable products', ['destination' => $destination['country']]);

            throw new ShippingQuoteException('DHL doesn\'t offer a delivery service to this address. Please check it, or contact us for help.');
        }

        [$providerAmount, $providerCurrency] = $product['price'];
        $amountGhs = strtoupper($providerCurrency) === config('shop.base_currency', 'GHS')
            ? round($providerAmount, 2)
            : $this->currency->toBase($providerAmount, $providerCurrency);

        if ($amountGhs === null) {
            Log::error('No exchange rate to convert DHL rate', ['currency' => $providerCurrency]);

            throw new ShippingQuoteException('We couldn\'t convert the DHL rate right now. Please try again shortly.');
        }

        return [
            'provider' => 'dhl_express',
            'amount' => $amountGhs,
            'currency' => config('shop.base_currency', 'GHS'),
            'provider_amount' => round($providerAmount, 2),
            'provider_currency' => strtoupper($providerCurrency),
            'service' => $product['name'],
            'product_code' => $product['code'],
            'estimated_delivery' => $product['estimated_delivery'],
            'transit_days' => $product['transit_days'],
            'packages' => $parcel['packages'],
            'actual_weight' => $parcel['actual_weight'],
            'volumetric_weight' => $parcel['volumetric_weight'],
            'chargeable_weight' => $product['billed_weight'] ?? $parcel['chargeable_weight'],
            'environment' => $dhl['environment'],
            'quoted_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes((int) $dhl['quote_minutes'])->toIso8601String(),
        ];
    }

    /**
     * The configured product, or the cheapest product with a price.
     */
    private function pickProduct(array $products): ?array
    {
        $options = [];
        foreach ($products as $product) {
            $price = $this->price($product['totalPrice'] ?? []);
            if (! $price) {
                continue;
            }
            $options[] = [
                'name' => $product['productName'] ?? 'DHL Express',
                'code' => $product['productCode'] ?? null,
                'price' => $price,
                'estimated_delivery' => $product['deliveryCapabilities']['estimatedDeliveryDateAndTime'] ?? null,
                'transit_days' => isset($product['deliveryCapabilities']['totalTransitDays']) ? (int) $product['deliveryCapabilities']['totalTransitDays'] : null,
                'billed_weight' => isset($product['weight']['volumetric'], $product['weight']['provided'])
                    ? max((float) $product['weight']['volumetric'], (float) $product['weight']['provided'])
                    : null,
            ];
        }

        if ($code = config('shipping.dhl.product_code')) {
            $options = array_values(array_filter($options, fn ($o) => $o['code'] === $code)) ?: $options;
        }

        usort($options, fn ($a, $b) => $a['price'][0] <=> $b['price'][0]);

        return $options[0] ?? null;
    }

    /**
     * Price in the account's billing currency (BILLC), else the first priced entry.
     *
     * @return array{0: float, 1: string}|null
     */
    private function price(array $totalPrices): ?array
    {
        $priced = array_values(array_filter($totalPrices, fn ($p) => isset($p['price'], $p['priceCurrency']) && (float) $p['price'] > 0));
        $billing = array_values(array_filter($priced, fn ($p) => ($p['currencyType'] ?? null) === 'BILLC'));
        $chosen = $billing[0] ?? $priced[0] ?? null;

        return $chosen ? [(float) $chosen['price'], (string) $chosen['priceCurrency']] : null;
    }

    /**
     * DHL validation messages are address-related and safe to show; anything long or technical is not.
     */
    private function customerSafeDetail(string $detail): ?string
    {
        $detail = trim(strip_tags($detail));

        return $detail !== '' && mb_strlen($detail) <= 160 && ! str_contains(mb_strtolower($detail), 'account') ? '(' . $detail . ')' : null;
    }
}
