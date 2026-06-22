<?php

return [
    /**
     * Paystack Configuration
     */
    'paystack_public_key' => env('PAYSTACK_PUBLIC_KEY'),
    'paystack_secret_key' => env('PAYSTACK_SECRET_KEY'),

    /**
     * Currency
     */
    'currency' => env('PAYMENT_CURRENCY', 'GHS'),

    /**
     * Stripe Configuration (for future use)
     */
    'stripe_public_key' => env('STRIPE_PUBLIC_KEY'),
    'stripe_secret_key' => env('STRIPE_SECRET_KEY'),
];
