<?php

return [

    /*
    | local: password login against the identity mirror.
    | oidc: future shared login at auth.sinaptik.com.tr. The driver class is a stub.
    */
    'driver' => env('IDENTITY_DRIVER', 'local'),

    'local_issuer' => env('IDENTITY_LOCAL_ISSUER', 'local'),

    'oidc' => [
        'issuer' => env('OIDC_ISSUER', ''),
        'client_id' => env('OIDC_CLIENT_ID', ''),
        'client_secret' => env('OIDC_CLIENT_SECRET', ''),
    ],

];
