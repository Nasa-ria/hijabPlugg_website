<?php

/*
|--------------------------------------------------------------------------
| Shipping
|--------------------------------------------------------------------------
| Ghana: the customer pays the courier on delivery, so nothing is charged at checkout.
| Everywhere else: DHL Express rates are requested live from the MyDHL API
| (developer.dhl.com → "DHL Express - MyDHL API"). No prices are hard-coded.
*/

return [
    // Orders delivered here use pay-on-delivery shipping
    'domestic_country' => env('SHIPPING_DOMESTIC_COUNTRY', 'GH'),

    // Where parcels are sent from (used as the DHL shipper address)
    'origin' => [
        'country' => env('SHIPPING_ORIGIN_COUNTRY', 'GH'),
        'city' => env('SHIPPING_ORIGIN_CITY', 'Accra'),
        'postal_code' => env('SHIPPING_ORIGIN_POSTAL_CODE', ''),
        'address' => env('SHIPPING_ORIGIN_ADDRESS', ''),
    ],

    'dhl' => [
        'api_key' => env('DHL_API_KEY'),
        'api_secret' => env('DHL_API_SECRET'),
        'account_number' => env('DHL_ACCOUNT_NUMBER'),

        // "test" uses DHL's sandbox, "production" returns your real account rates
        'environment' => env('DHL_ENVIRONMENT', 'test'),
        'urls' => [
            'test' => 'https://express.api.dhl.com/mydhlapi/test',
            'production' => 'https://express.api.dhl.com/mydhlapi',
        ],

        // Optional DHL product code to always use (e.g. "P" = Express Worldwide).
        // Empty: the cheapest product DHL offers for the route is used.
        'product_code' => env('DHL_PRODUCT_CODE'),

        'timeout' => (int) env('DHL_TIMEOUT', 15),

        // How long a quote stays valid at checkout before it is requested again
        'quote_minutes' => (int) env('DHL_QUOTE_MINUTES', 30),

        // Days from today the parcel is expected to be handed to DHL
        'pickup_days' => (int) env('DHL_PICKUP_DAYS', 1),
    ],

    /*
    | Parcel building. Each product's own weight and dimensions are used; these values
    | only fill gaps for products where they haven't been entered yet.
    | Folded items are stacked: length/width = the largest item, height = sum of heights.
    */
    'parcel' => [
        'default_item_weight_kg' => (float) env('SHIPPING_DEFAULT_ITEM_WEIGHT_KG', 0.3),
        'default_item_dimensions_cm' => [
            'length' => (float) env('SHIPPING_DEFAULT_ITEM_LENGTH_CM', 30),
            'width' => (float) env('SHIPPING_DEFAULT_ITEM_WIDTH_CM', 25),
            'height' => (float) env('SHIPPING_DEFAULT_ITEM_HEIGHT_CM', 3),
        ],
        'packaging_weight_kg' => (float) env('SHIPPING_PACKAGING_WEIGHT_KG', 0.2),
        'packaging_padding_cm' => (float) env('SHIPPING_PACKAGING_PADDING_CM', 2),
        // A new box is started when the stack would exceed either limit
        'max_package_height_cm' => (float) env('SHIPPING_MAX_PACKAGE_HEIGHT_CM', 60),
        'max_package_weight_kg' => (float) env('SHIPPING_MAX_PACKAGE_WEIGHT_KG', 30),
        // DHL Express volumetric divisor (cm³ per kg), used to show the chargeable weight
        'volumetric_divisor' => (int) env('SHIPPING_VOLUMETRIC_DIVISOR', 5000),
    ],
];
