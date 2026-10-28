<?php

namespace App\Contracts\Google;

interface GoogleOAuthClient
{
    /**
     * @return array{url: string, state: string}
     */
    public function authorizationUrl(string $state): array;

    /**
     * @return array<string, mixed>
     */
    public function exchangeCode(string $code): array;
}
