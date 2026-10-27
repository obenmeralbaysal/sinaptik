<?php

/**
 * Public plan catalog consumed by Sinapp (GET /api/sinapp/plans).
 * site_limit stays on the local plan row and is stripped from the public document.
 */
return [
    'product' => 'sinaptik-analytics',
    'product_code' => 'analytics',
    'currency' => 'TRY',
    'vat_rate' => 20,
    'updated_at' => '2026-10-27T00:00:00Z',
    'plans' => [
        [
            'code' => 'analytics-starter-monthly',
            'name' => 'Başlangıç',
            'interval' => 'monthly',
            'price_net' => '249.00',
            'price_gross' => '298.80',
            'features' => ['1 site', 'Search Console özeti'],
            'highlighted' => false,
            'active' => true,
            'site_limit' => 1,
        ],
        [
            'code' => 'analytics-pro-monthly',
            'name' => 'Pro',
            'interval' => 'monthly',
            'price_net' => '749.00',
            'price_gross' => '898.80',
            'features' => ['10 site', 'Search Console ve GA4'],
            'highlighted' => true,
            'active' => true,
            'site_limit' => 10,
        ],
    ],
];
