<?php

namespace App\Services\Google;

use App\Contracts\Google\SearchConsoleClient;

class NullSearchConsoleClient implements SearchConsoleClient
{
    public function listSites(string $accessToken): array
    {
        return [];
    }
}
