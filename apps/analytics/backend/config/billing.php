<?php

return [
    'gateway' => env('BILLING_GATEWAY', 'fake'),

    'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://127.0.0.1:3027'), '/'),

    'hub' => [
        'url' => rtrim((string) env('SINAPP_HUB_URL', 'https://sinapp.com.tr'), '/'),
        'checkout_path' => env('SINAPP_HUB_CHECKOUT_PATH', '/api/v1/checkout-sessions'),
        'integration_key' => env('SINAPP_HUB_KEY', ''),
        'secret' => env('SINAPP_HUB_SECRET', ''),
        'key_header' => env('SINAPP_HUB_KEY_HEADER', 'X-Integration-Key'),
        'timestamp_header' => env('SINAPP_HUB_TIMESTAMP_HEADER', 'X-Timestamp'),
        'signature_header' => env('SINAPP_HUB_SIGNATURE_HEADER', 'X-Signature'),
        'timestamp_tolerance' => (int) env('SINAPP_HUB_TIMESTAMP_TOLERANCE', 300),
        'webhook_path' => '/api/webhooks/billing/sinapp',
    ],

    'sinapp' => [
        'base_url' => rtrim((string) env('SINAPP_BASE_URL', 'https://sinapp.com.tr'), '/'),
        'purchase_path' => env('SINAPP_PURCHASE_PATH', '/satin-al/sinaptik-analytics'),
        'integration_key' => env('SINAPP_INTEGRATION_KEY', ''),
        'secret' => env('SINAPP_INTEGRATION_SECRET', ''),
        'product' => 'sinaptik-analytics',
        'product_code' => 'analytics',
        'currency' => 'TRY',
        'vat_rate' => 20,
        'timestamp_tolerance' => (int) env('SINAPP_SIGNATURE_TOLERANCE', 300),
        'nonce_ttl' => 600,
        'lookup_rate_limit' => 30,
    ],

    'events' => [
        'activated' => 'subscription.activated',
        'renewed' => 'subscription.renewed',
        'payment_failed' => 'subscription.payment_failed',
        'canceled' => 'subscription.canceled',
        'refunded' => 'payment.refunded',
        'credit_pack' => 'credit_pack.purchased',
        'addon' => 'addon.purchased',
    ],

    'dunning_attempts' => 3,
    'dunning_days' => 7,
];
