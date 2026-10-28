<?php

namespace App\Services\Meta;

use RuntimeException;

class SignedRequest
{
    public function parse(string $signedRequest): array
    {
        if (! str_contains($signedRequest, '.')) {
            throw new RuntimeException('signed_request bozuk.');
        }

        [$encodedSig, $payload] = explode('.', $signedRequest, 2);
        $sig = $this->b64($encodedSig);
        $secret = (string) config('services.meta.app_secret');
        $expected = hash_hmac('sha256', $payload, $secret, true);

        if (! hash_equals($expected, $sig)) {
            throw new RuntimeException('signed_request imzası geçersiz.');
        }

        $data = json_decode($this->b64($payload), true);
        if (! is_array($data)) {
            throw new RuntimeException('signed_request içeriği okunamadı.');
        }

        return $data;
    }

    private function b64(string $input): string
    {
        $replaced = strtr($input, '-_', '+/');
        $pad = strlen($replaced) % 4;
        if ($pad) {
            $replaced .= str_repeat('=', 4 - $pad);
        }

        return (string) base64_decode($replaced, true);
    }
}
