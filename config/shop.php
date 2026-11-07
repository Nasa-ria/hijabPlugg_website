<?php

/*
|--------------------------------------------------------------------------
| Checkout, inventory and storefront settings
|--------------------------------------------------------------------------
| Every monetary value in the database is Ghana Cedis (GHS). Nothing here
| changes that: the display currency only affects what customers see.
*/

return [
    // The store's accounting currency. Do not change: all stored amounts are GHS.
    'base_currency' => 'GHS',

    // Tax charged on the product subtotal (never on shipping). 0.05 = 5%.
    'tax_rate' => (float) env('SHOP_TAX_RATE', 0.05),

    'inventory' => [
        // Alert level used when a product has no low-stock level of its own
        'low_stock_threshold' => (int) env('INVENTORY_LOW_STOCK_THRESHOLD', 5),

        // Unpaid orders hold their stock this long, then are cancelled and the stock is released
        'release_unpaid_after_hours' => (int) env('INVENTORY_RELEASE_UNPAID_AFTER_HOURS', 48),

        // Largest Excel import accepted (rows)
        'import_max_rows' => (int) env('INVENTORY_IMPORT_MAX_ROWS', 2000),
    ],

    'storefront' => [
        // "New arrival" badge for products added within this many days
        'new_arrival_days' => (int) env('SHOP_NEW_ARRIVAL_DAYS', 30),

        // "Best seller" badge: the top N products by units sold over the last N days
        'best_seller_count' => (int) env('SHOP_BEST_SELLER_COUNT', 8),
        'best_seller_days' => (int) env('SHOP_BEST_SELLER_DAYS', 90),
    ],
];
