<?php

use App\Consent\GoogleConnectConsent;

$configuredVersion = env('GOOGLE_CONSENT_TEXT_VERSION');

return [

    'google' => [
        'text_version' => is_string($configuredVersion) && $configuredVersion !== ''
            ? $configuredVersion
            : GoogleConnectConsent::version(),
        'notice_path' => env('CONSENT_NOTICE_PATH', '/aydinlatma-metni'),
    ],

];
