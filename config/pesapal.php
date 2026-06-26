<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pesapal Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Pesapal v3 API integration.
    |
    */

    'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
    'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
    'environment' => env('PESAPAL_ENVIRONMENT', 'sandbox'),
    'base_url' => env('PESAPAL_BASE_URL', 'https://pay.pesapal.com/v3'),
    'ipn_id' => env('PESAPAL_IPN_ID'),
    'callback_url' => env('PESAPAL_CALLBACK_URL'),

    'currency' => env('PESAPAL_CURRENCY', 'UGX'),

    /*
    |--------------------------------------------------------------------------
    | IPN Validation
    |--------------------------------------------------------------------------
    |
    | Pesapal IPN source IPs for verification.
    | In production, these should be the actual Pesapal IP ranges.
    |
    */

    'ipn_allowed_ips' => [
        'sandbox' => [],
        'live' => [],
    ],
];
