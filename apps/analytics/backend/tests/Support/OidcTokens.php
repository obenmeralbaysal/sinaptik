<?php

namespace Tests\Support;

use App\Identity\Oidc\Pkce;

final class OidcTokens
{
    /**
     * @return array{pem: string, jwk: array<string, string>}
     */
    public static function key(string $kid = 'test-key'): array
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if ($key === false) {
            throw new \RuntimeException('openssl key generation failed');
        }

        openssl_pkey_export($key, $pem);
        $details = openssl_pkey_get_details($key);
        if (! is_array($details) || ! isset($details['rsa']['n'], $details['rsa']['e'])) {
            throw new \RuntimeException('openssl key details missing');
        }

        return [
            'pem' => $pem,
            'jwk' => [
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => $kid,
                'n' => self::jwkParam($details['rsa']['n']),
                'e' => self::jwkParam($details['rsa']['e']),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $claims
     * @param  array<string, mixed>  $header
     */
    public static function jwt(string $pem, array $claims, array $header = []): string
    {
        $header = $header !== [] ? $header : ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => 'test-key'];
        $encodedHeader = Pkce::base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = Pkce::base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR));
        $signingInput = $encodedHeader.'.'.$encodedPayload;
        $signed = openssl_sign($signingInput, $signature, $pem, OPENSSL_ALGO_SHA256);
        if ($signed !== true) {
            throw new \RuntimeException('openssl sign failed');
        }

        return $signingInput.'.'.Pkce::base64UrlEncode($signature);
    }

    private static function jwkParam(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '') {
            $bytes = "\x00";
        }

        return Pkce::base64UrlEncode($bytes);
    }
}
