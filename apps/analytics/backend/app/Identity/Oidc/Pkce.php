<?php

namespace App\Identity\Oidc;

final class Pkce
{
    public static function randomToken(): string
    {
        return self::base64UrlEncode(random_bytes(32));
    }

    public static function verifier(): string
    {
        return self::randomToken();
    }

    public static function challengeS256(string $verifier): string
    {
        return self::base64UrlEncode(hash('sha256', $verifier, true));
    }

    public static function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        $b64 = strtr($data, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad !== 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        $raw = base64_decode($b64, true);
        if ($raw === false) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        return $raw;
    }
}
