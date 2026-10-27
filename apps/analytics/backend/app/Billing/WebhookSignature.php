<?php

namespace App\Billing;

final class WebhookSignature
{
    public function timestampFresh(string $timestamp, int $tolerance): bool
    {
        if (preg_match('/^\d{1,12}$/', $timestamp) !== 1) {
            return false;
        }

        return abs(time() - (int) $timestamp) <= $tolerance;
    }

    public function verify(string $secret, string $timestamp, string $rawBody, string $signature): bool
    {
        if ($secret === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

        return hash_equals($expected, strtolower($signature));
    }
}
