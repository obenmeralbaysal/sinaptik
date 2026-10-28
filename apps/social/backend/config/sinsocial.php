<?php

return [
    'frontend_url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost:3000')),
    'token_key' => env('TOKEN_ENCRYPTION_KEY'),
    'token_key_previous' => env('TOKEN_ENCRYPTION_KEY_PREVIOUS'),
    'trial_days' => (int) env('TRIAL_DAYS', 14),
    'trial_plan' => env('TRIAL_PLAN', 'pro'),
    'deletion_grace_days' => (int) env('DELETION_GRACE_DAYS', 14),
    'lapsed_purge_days' => (int) env('LAPSED_PURGE_DAYS', 30),
    'media_disk' => env('MEDIA_DISK', 'media_local'),
    'media_url_ttl_minutes' => (int) env('MEDIA_URL_TTL_MINUTES', 120),
];
