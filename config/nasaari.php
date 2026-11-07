<?php

/*
|--------------------------------------------------------------------------
| Nasaari Store details
|--------------------------------------------------------------------------
| Social links and contact details shown on the home page, footer and
| contact page. Set them in .env; anything left empty is simply hidden.
*/

return [
    'social' => [
        // Full profile links, e.g. https://instagram.com/nasaari.store
        'instagram' => env('SOCIAL_INSTAGRAM'),
        'tiktok' => env('SOCIAL_TIKTOK'),
        'facebook' => env('SOCIAL_FACEBOOK'),
        'x' => env('SOCIAL_X'),
        'snapchat' => env('SOCIAL_SNAPCHAT'),
        'pinterest' => env('SOCIAL_PINTEREST'),
        'youtube' => env('SOCIAL_YOUTUBE'),
        // WhatsApp: your number in international format without + or spaces, e.g. 233241234567
        'whatsapp' => env('SOCIAL_WHATSAPP'),
    ],

    // The handle shown next to the icons, e.g. @nasaari.store
    'handle' => env('SOCIAL_HANDLE'),

    // Who gets the "New order" email. Several addresses: separate with commas.
    'admin_order_emails' => array_values(array_filter(array_map('trim', explode(',', (string) (env('ADMIN_ORDER_EMAILS') ?: 'orders@nasaari.store'))))),

    'contact' => [
        // Where contact form messages are emailed (also saved in Admin → Messages)
        'email' => env('CONTACT_EMAIL') ?: env('MAIL_FROM_ADDRESS'),
        'phone' => env('CONTACT_PHONE'),
        'hours' => env('CONTACT_HOURS') ?: 'Monday – Saturday, 9:00 am – 6:00 pm',
    ],
];
