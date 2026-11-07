<?php

namespace App\Services;

use App\Support\Countries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Display currency for the storefront.
 *
 * GHS is the source of truth: every amount passed in here is GHS, and nothing this
 * class returns is ever written back to the database as a price. Converted amounts
 * are for display only; Paystack always charges the GHS total.
 */
class CurrencyService
{
    public const SESSION_KEY = 'display_currency';
    public const COUNTRY_SESSION_KEY = 'detected_country';
    public const COOKIE = 'display_currency';

    private const RATES_CACHE = 'fx:rates:GHS';
    private const RATES_LAST_GOOD = 'fx:rates:GHS:last-good';

    private ?string $resolvedCurrency = null;
    private ?string $resolvedCountry = null;
    private bool $countryResolved = false;
    private ?array $rates = null;
    private ?int $requestId = null;
    private ?Request $request = null;

    /**
     * The current request. Per-request answers are forgotten when a new request starts.
     */
    private function request(): Request
    {
        $request = request();
        if ($this->requestId !== spl_object_id($request)) {
            $this->requestId = spl_object_id($request);
            $this->request = $request;
            $this->resolvedCurrency = null;
            $this->resolvedCountry = null;
            $this->countryResolved = false;
        }

        return $this->request;
    }

    public function base(): string
    {
        return config('shop.base_currency', 'GHS');
    }

    /**
     * Currency shown to this visitor (falls back to GHS when no exchange rate is known).
     */
    public function current(): string
    {
        $this->request();
        if ($this->resolvedCurrency !== null) {
            return $this->resolvedCurrency;
        }

        $chosen = $this->chosenCurrency();
        $currency = $chosen ?? $this->currencyForCountry($this->country());

        if ($currency !== $this->base() && $this->rate($currency) === null) {
            $currency = $this->base();
        }

        return $this->resolvedCurrency = $currency;
    }

    /**
     * The currency the visitor picked in the switcher, if any.
     */
    public function chosenCurrency(): ?string
    {
        $choice = $this->request()->hasSession() ? $this->request()->session()->get(self::SESSION_KEY) : null;
        $choice ??= $this->request()->cookie(self::COOKIE);

        return is_string($choice) && $this->isSupported($choice) ? $choice : null;
    }

    public function choose(string $currency): void
    {
        abort_unless($this->isSupported($currency), 422);

        $this->request()->session()->put(self::SESSION_KEY, $currency);
        $this->resolvedCurrency = null;
    }

    public function isSupported(string $currency): bool
    {
        return array_key_exists($currency, config('currency.supported', []));
    }

    public function currencyForCountry(?string $country): string
    {
        return $country === null || strtoupper($country) === strtoupper(config('currency.home_country', 'GH'))
            ? config('currency.home_currency', 'GHS')
            : config('currency.international_currency', 'USD');
    }

    public function isConverted(): bool
    {
        return $this->current() !== $this->base();
    }

    /**
     * Visitor's country (ISO 3166-1 alpha-2), or null when unknown.
     * Order: account country → CDN header → IP lookup. Remembered for the session.
     */
    public function country(): ?string
    {
        $this->request();
        if ($this->countryResolved) {
            return $this->resolvedCountry;
        }
        $this->countryResolved = true;

        $fromUser = Countries::code($this->request()->user()?->country);
        if ($fromUser) {
            return $this->resolvedCountry = $fromUser;
        }

        $session = $this->request()->hasSession() ? $this->request()->session() : null;
        if ($session && $session->has(self::COUNTRY_SESSION_KEY)) {
            return $this->resolvedCountry = $session->get(self::COUNTRY_SESSION_KEY) ?: null;
        }

        $country = $this->countryFromHeaders() ?? $this->countryFromIpLookup();
        $session?->put(self::COUNTRY_SESSION_KEY, $country ?? '');

        return $this->resolvedCountry = $country;
    }

    public function isHomeCountry(): bool
    {
        $country = $this->country();

        return $country === null || $country === strtoupper(config('currency.home_country', 'GH'));
    }

    private function countryFromHeaders(): ?string
    {
        foreach (config('currency.detection.headers', []) as $header) {
            $country = $this->normaliseCountry($this->request()->header($header));
            if ($country) {
                return $country;
            }
        }

        return null;
    }

    private function countryFromIpLookup(): ?string
    {
        $url = (string) config('currency.detection.lookup_url');
        $ip = (string) $this->request()->ip();

        if ($url === '' || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        return Cache::remember('geoip:' . sha1($ip), now()->addDays((int) config('currency.detection.cache_days', 7)), function () use ($url, $ip) {
            try {
                $response = Http::timeout((float) config('currency.detection.lookup_timeout', 1.5))
                    ->get(str_replace('{ip}', rawurlencode($ip), $url));

                if (! $response->successful()) {
                    return null;
                }

                // Plain-text services return "GH"; JSON services use country / country_code / countryCode
                $json = $response->json();
                $value = is_array($json)
                    ? ($json['country_code'] ?? $json['countryCode'] ?? $json['country'] ?? null)
                    : trim($response->body());

                return $this->normaliseCountry(is_string($value) ? $value : null);
            } catch (\Throwable $e) {
                Log::info('Country lookup failed: ' . $e->getMessage());

                return null;
            }
        });
    }

    private function normaliseCountry(?string $value): ?string
    {
        $value = strtoupper(trim((string) $value));

        // XX / T1 are "unknown" / Tor in Cloudflare
        return preg_match('/^[A-Z]{2}$/', $value) && ! in_array($value, ['XX', 'T1'], true) ? $value : null;
    }

    /**
     * Units of $currency per 1 GHS, or null when no rate is available.
     */
    public function rate(string $currency): ?float
    {
        if ($currency === $this->base()) {
            return 1.0;
        }

        $rate = $this->rates()[$currency] ?? null;

        return $rate && $rate > 0 ? (float) $rate : null;
    }

    /**
     * All exchange rates for 1 GHS: cached → live → last good rates → configured fallback.
     */
    public function rates(): array
    {
        if ($this->rates !== null) {
            return $this->rates;
        }

        $rates = Cache::get(self::RATES_CACHE);
        if (! is_array($rates)) {
            $rates = $this->fetchRates();
            if ($rates) {
                Cache::put(self::RATES_CACHE, $rates, now()->addMinutes((int) config('currency.rates.cache_minutes', 720)));
                Cache::forever(self::RATES_LAST_GOOD, $rates);
            } else {
                $rates = Cache::get(self::RATES_LAST_GOOD, []);
                // Try the source again in 10 minutes rather than on every request
                Cache::put(self::RATES_CACHE, $rates, now()->addMinutes(10));
            }
        }

        foreach ((array) config('currency.rates.fallback', []) as $code => $fallback) {
            if (empty($rates[$code]) && is_numeric($fallback) && $fallback > 0) {
                $rates[$code] = (float) $fallback;
            }
        }

        return $this->rates = $rates;
    }

    private function fetchRates(): ?array
    {
        $url = (string) config('currency.rates.url');
        if ($url === '') {
            return null;
        }

        try {
            $request = Http::timeout((int) config('currency.rates.timeout', 5))->acceptJson();
            if ($key = config('currency.rates.api_key')) {
                $request = $request->withToken($key);
            }
            $response = $request->get($url);
            $rates = $response->json('rates') ?? $response->json('conversion_rates');

            if ($response->successful() && is_array($rates) && ! empty($rates)) {
                return array_map('floatval', array_filter($rates, 'is_numeric'));
            }

            Log::warning('Exchange rate source returned no rates', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::warning('Exchange rate source unreachable: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Forget cached rates so the next request fetches fresh ones.
     */
    public function refreshRates(): array
    {
        Cache::forget(self::RATES_CACHE);
        $this->rates = null;

        return $this->rates();
    }

    /**
     * Convert a GHS amount for display. Never store the result as a price.
     */
    public function convert(float $ghs, ?string $currency = null): float
    {
        $currency ??= $this->current();
        $rate = $this->rate($currency);

        return $rate === null ? $ghs : $ghs * $rate;
    }

    /**
     * Convert an amount in another currency into GHS (e.g. a DHL rate billed in USD).
     */
    public function toBase(float $amount, string $currency): ?float
    {
        $rate = $this->rate(strtoupper($currency));

        return $rate === null ? null : round($amount / $rate, 2);
    }

    /**
     * Format a GHS amount in the visitor's display currency, e.g. "GH₵120.00" or "$7.80".
     */
    public function format(float|string|null $ghs, ?string $currency = null): string
    {
        $currency ??= $this->current();

        return $this->formatIn($this->convert((float) $ghs, $currency), $currency);
    }

    /**
     * Format an order amount (GHS) for the visitor. When they view it in the currency they
     * ordered in, the rate saved with the order is used, so past orders don't drift with the rate.
     */
    public function formatForOrder(\App\Models\Order $order, float|string|null $ghs): string
    {
        $currency = $this->current();

        if ($currency === $order->display_currency && $order->display_exchange_rate && $currency !== $this->base()) {
            return $this->formatIn((float) $ghs * (float) $order->display_exchange_rate, $currency);
        }

        return $this->format($ghs, $currency);
    }

    /**
     * Format an amount already in $currency.
     */
    public function formatIn(float $amount, string $currency): string
    {
        $meta = config("currency.supported.$currency", ['symbol' => $currency . ' ', 'decimals' => 2]);
        $sign = $amount < 0 ? '-' : '';

        return $sign . $meta['symbol'] . number_format(abs($amount), $meta['decimals']);
    }

    /**
     * Always GHS, e.g. for "you will be charged" lines and admin pages.
     */
    public function formatBase(float|string|null $ghs): string
    {
        return $this->formatIn((float) $ghs, $this->base());
    }

    public function symbol(?string $currency = null): string
    {
        return config('currency.supported.' . ($currency ?? $this->current()) . '.symbol', '');
    }

    /**
     * Data the storefront JavaScript needs to format prices the same way.
     */
    public function jsConfig(): array
    {
        $currency = $this->current();

        return [
            'code' => $currency,
            'symbol' => $this->symbol($currency),
            'rate' => $this->rate($currency) ?? 1.0,
            'decimals' => config("currency.supported.$currency.decimals", 2),
            'base' => $this->base(),
        ];
    }
}
