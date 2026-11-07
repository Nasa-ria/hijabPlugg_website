<?php

/*
|--------------------------------------------------------------------------
| Display currency
|--------------------------------------------------------------------------
| Customers in Ghana see Ghana Cedis; everyone else sees US Dollars.
| This is a DISPLAY conversion only. Orders, payments and reports stay in GHS,
| and Paystack always charges the GHS amount.
*/

return [
    'home_country' => env('CURRENCY_HOME_COUNTRY', 'GH'),

    // Currency shown to customers in the home country, and to everyone else
    'home_currency' => 'GHS',
    'international_currency' => env('CURRENCY_INTERNATIONAL', 'USD'),

    // Currencies a customer may pick by hand
    'supported' => [
        'GHS' => ['symbol' => 'GH₵', 'name' => 'Ghana Cedi', 'decimals' => 2],
        'USD' => ['symbol' => '$', 'name' => 'US Dollar', 'decimals' => 2],
    ],

    /*
    | Exchange rates. The source must return JSON with a "rates" object keyed by
    | currency code for 1 unit of GHS, e.g. {"rates": {"USD": 0.0645, ...}}.
    | Default: ExchangeRate-API's open endpoint (no key, updated daily).
    */
    'rates' => [
        'url' => env('EXCHANGE_RATE_URL', 'https://open.er-api.com/v6/latest/GHS'),
        'api_key' => env('EXCHANGE_RATE_API_KEY'), // sent as a Bearer token when set
        'cache_minutes' => (int) env('EXCHANGE_RATE_CACHE_MINUTES', 720),
        'timeout' => (int) env('EXCHANGE_RATE_TIMEOUT', 5),

        // Used only when the source is unreachable and no earlier rate is cached.
        // USD per 1 GHS, e.g. 0.065. Leave empty to show GHS when no rate is known.
        'fallback' => [
            'USD' => env('EXCHANGE_RATE_FALLBACK_GHS_USD'),
        ],
    ],

    /*
    | Country detection, tried in this order:
    |   1. the customer's own choice (currency switcher)
    |   2. the country on their account
    |   3. a country header added by your CDN / host (Cloudflare, CloudFront, Vercel…)
    |   4. an IP lookup service ({ip} is replaced). Set GEOIP_LOOKUP_URL= (empty) to turn off.
    |   5. the home country
    */
    'detection' => [
        'headers' => ['CF-IPCountry', 'CloudFront-Viewer-Country', 'X-Vercel-IP-Country', 'X-Country-Code'],
        'lookup_url' => env('GEOIP_LOOKUP_URL', 'https://ipapi.co/{ip}/country/'),
        'lookup_timeout' => (float) env('GEOIP_LOOKUP_TIMEOUT', 1.5),
        'cache_days' => 7,
    ],
];
