<?php

namespace App\Services\Google;

use App\Contracts\Google\GoogleOAuthClient;

class NullGoogleOAuthClient implements GoogleOAuthClient
{
    public function authorizationUrl(string $state): array
    {
        return [
            'url' => '',
            'state' => $state,
        ];
    }

    public function exchangeCode(string $code): array
    {
        return [];
    }
}
