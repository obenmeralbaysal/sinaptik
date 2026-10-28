<?php

namespace App\Contracts\Google;

interface SearchConsoleClient
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listSites(string $accessToken): array;
}
