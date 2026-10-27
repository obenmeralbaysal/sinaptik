<?php

namespace App\Services\Google;

use App\Contracts\Google\AnalyticsDataClient;

class NullAnalyticsDataClient implements AnalyticsDataClient
{
    public function runReport(string $accessToken, string $propertyId, array $query): array
    {
        return ['rows' => []];
    }
}
