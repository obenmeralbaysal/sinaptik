<?php

namespace App\Services\Google;

use Illuminate\Support\Facades\Http;

class GoogleTokenRevoker
{
    public const ENDPOINT = 'https://oauth2.googleapis.com/revoke';

    public function revoke(string $token): void
    {
        Http::asForm()
            ->timeout(5)
            ->connectTimeout(3)
            ->post(self::ENDPOINT, ['token' => $token])
            ->throw();
    }
}
