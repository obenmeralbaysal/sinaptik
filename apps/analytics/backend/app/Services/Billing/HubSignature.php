<?php

namespace App\Services\Billing;

use Illuminate\Http\Request;

class HubSignature
{
    public function assertValid(Request $request): void
    {
        $keyHeader = (string) config('billing.hub.key_header');
        $tsHeader = (string) config('billing.hub.timestamp_header');
        $sigHeader = (string) config('billing.hub.signature_header');

        $providedKey = (string) $request->header($keyHeader, '');
        $timestamp = (string) $request->header($tsHeader, '');
        $signature = (string) $request->header($sigHeader, '');
        $expectedKey = (string) config('billing.hub.integration_key');
        $secret = (string) config('billing.hub.secret');

        $keyOk = $expectedKey !== '' && $providedKey !== ''
            && hash_equals(hash('sha256', $expectedKey), hash('sha256', $providedKey));
        if (! $keyOk || $timestamp === '' || $signature === '' || $secret === '') {
            abort(401, 'Geçersiz faturalama imzası.');
        }

        if (! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > (int) config('billing.hub.timestamp_tolerance')) {
            abort(401, 'Faturalama zaman damgası geçersiz.');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        if (! hash_equals($expected, $signature)) {
            abort(401, 'Geçersiz faturalama imzası.');
        }
    }
}
