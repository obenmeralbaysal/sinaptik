<?php

namespace App\Contracts\Google;

interface AnalyticsDataClient
{
    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function runReport(string $accessToken, string $propertyId, array $query): array;
}
