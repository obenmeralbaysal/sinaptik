<?php

return [

    /*
    | local: password login against the identity mirror.
    | oidc: Authorization Code + PKCE (S256) against the shared issuer (Zitadel).
    */
    'driver' => env('IDENTITY_DRIVER', 'local'),

    'local_issuer' => env('IDENTITY_LOCAL_ISSUER', 'local'),

    /*
    | When true, local sessions satisfy RequireMfaForPrivileged.
    | Ignored while IDENTITY_DRIVER=oidc; that driver reads the id_token amr claim.
    */
    'local_mfa' => filter_var(env('IDENTITY_LOCAL_MFA', true), FILTER_VALIDATE_BOOLEAN),

    'oidc' => [
        // Production issuer target: https://auth.sinaptik.com.tr — leave empty for local.
        'issuer' => env('OIDC_ISSUER', ''),
        'client_id' => env('OIDC_CLIENT_ID', ''),
        'client_secret' => env('OIDC_CLIENT_SECRET', ''),
        // Production redirect: https://social.sinaptik.com.tr/auth/callback
        'redirect_uri' => env('OIDC_REDIRECT_URI', ''),
        'scopes' => env('OIDC_SCOPES', 'openid profile email'),
        'idp_hint' => env('OIDC_IDP_HINT', ''),
        'post_login_redirect' => rtrim((string) env('FRONTEND_URL', env('APP_URL', 'http://localhost:3000')), '/').'/uygulama',
        'leeway' => 60,
    ],

];
