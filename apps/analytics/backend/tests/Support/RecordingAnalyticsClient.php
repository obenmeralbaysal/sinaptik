<?php

namespace Tests\Support;

use App\Contracts\Google\AnalyticsDataClient;

class RecordingAnalyticsClient implements AnalyticsDataClient
{
    public int $calls = 0;

    public function runReport(string $accessToken, string $propertyId, array $query): array
    {
        $this->calls++;

        return ['rows' => []];
    }
}
