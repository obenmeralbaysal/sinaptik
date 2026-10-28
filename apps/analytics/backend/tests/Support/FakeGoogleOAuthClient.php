<?php

namespace Tests\Support;

use App\Contracts\Google\GoogleOAuthClient;

class FakeGoogleOAuthClient implements GoogleOAuthClient
{
    public int $exchanges = 0;

    public function authorizationUrl(string $state): array
    {
        return [
            'url' => 'https://accounts.google.com/o/oauth2/v2/auth?state='.$state,
            'state' => $state,
        ];
    }

    public function exchangeCode(string $code): array
    {
        $this->exchanges++;

        return [
            'access_token' => 'access-from-code',
            'refresh_token' => 'refresh-from-code',
        ];
    }
}
