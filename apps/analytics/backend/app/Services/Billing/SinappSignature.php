<?php

namespace App\Services\Billing;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class SinappSignature
{
    public function assertLookup(Request $request): void
    {
        $key = (string) $request->header('X-Sinapp-Key', '');
        $timestamp = (string) $request->header('X-Sinapp-Timestamp', '');
        $nonce = (string) $request->header('X-Sinapp-Nonce', '');
        $signature = strtolower((string) $request->header('X-Sinapp-Signature', ''));

        $this->throttle($key !== '' ? $key : (string) $request->ip(), (string) $request->ip());

        $expectedKey = (string) config('billing.sinapp.integration_key');
        $secret = (string) config('billing.sinapp.secret');
        if ($expectedKey === '' || $secret === '' || $key === '' || $timestamp === '' || $nonce === '' || $signature === '') {
            abort(401, 'Geçersiz Sinapp imzası.');
        }

        if (strlen($nonce) > 128 || ! hash_equals(hash('sha256', $expectedKey), hash('sha256', $key))) {
            abort(401, 'Geçersiz Sinapp imzası.');
        }

        $this->assertTimestamp($timestamp);

        $canonical = $timestamp."\n".$nonce."\n".strtoupper($request->getMethod())."\n".$request->getPathInfo()."\n".hash('sha256', $request->getContent());
        $expected = hash_hmac('sha256', $canonical, $secret);
        if (! hash_equals($expected, $signature)) {
            abort(401, 'Geçersiz Sinapp imzası.');
        }

        $nonceKey = 'sinapp.lookup.nonce.'.hash('sha256', $nonce);
        $ttl = (int) config('billing.sinapp.nonce_ttl');
        if (! Cache::add($nonceKey, 1, now()->addSeconds($ttl))) {
            abort(401, 'Sinapp nonce yeniden kullanıldı.');
        }
    }

    public function assertWebhook(Request $request): void
    {
        $timestamp = (string) $request->header('X-Sinapp-Timestamp', '');
        $signature = strtolower((string) $request->header('X-Sinapp-Signature', ''));
        $secret = (string) config('billing.sinapp.secret');

        if ($secret === '' || $timestamp === '' || $signature === '') {
            abort(401, 'Geçersiz Sinapp imzası.');
        }

        $this->assertTimestamp($timestamp);

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        if (! hash_equals($expected, $signature)) {
            abort(401, 'Geçersiz Sinapp imzası.');
        }
    }

    private function assertTimestamp(string $timestamp): void
    {
        $tolerance = (int) config('billing.sinapp.timestamp_tolerance');
        if (! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > $tolerance) {
            abort(401, 'Sinapp zaman damgası geçersiz.');
        }
    }

    private function throttle(string $keyBucket, string $ip): void
    {
        $limit = (int) config('billing.sinapp.lookup_rate_limit');
        foreach (['key' => $keyBucket, 'ip' => $ip] as $kind => $value) {
            $bucket = 'sinapp.lookup.'.$kind.'.'.hash('sha256', $value);
            if (RateLimiter::tooManyAttempts($bucket, $limit)) {
                abort(response()->json(['message' => 'Çok fazla istek.'], 429));
            }
            RateLimiter::hit($bucket, 60);
        }
    }
}
