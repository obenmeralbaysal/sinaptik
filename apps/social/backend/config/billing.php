<?php

return [
    /*
    | Hub-specific names live only in this file. Adjust them when
    | sinapp.com.tr docs/INTEGRATION.md is finalized.
    */
    'gateway' => env('BILLING_GATEWAY', 'fake'),

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

    'addons' => [
        'extra_seat' => ['metric' => 'seats', 'quantity' => 1],
        'extra_account' => ['metric' => 'social_accounts', 'quantity' => 1],
        'x_credit_pack' => ['currency' => 'x_credit', 'credits' => 1000],
        'ai_credit_pack' => ['currency' => 'ai_credit', 'credits' => 500],
    ],
];
