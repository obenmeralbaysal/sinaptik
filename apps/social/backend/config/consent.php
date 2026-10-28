<?php

return [

    'meta' => [
        'text_version' => env('META_CONSENT_TEXT_VERSION', 'v1'),
        'notice_url' => env('META_CONSENT_NOTICE_URL', '/aydinlatma-metni'),
        'text' => trim((string) file_get_contents(__DIR__.'/../resources/consent/meta.txt')),
    ],

];
