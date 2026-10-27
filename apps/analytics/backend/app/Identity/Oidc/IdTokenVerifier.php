<?php

namespace App\Identity\Oidc;

final class IdTokenVerifier
{
    /**
     * @param  array<string, mixed>  $jwks
     * @return array<string, mixed>
     */
    public static function verify(
        string $jwt,
        array $jwks,
        string $issuer,
        string $audience,
        string $nonce,
        ?string $code = null,
        ?int $now = null,
        int $leeway = 60,
    ): array {
        if (strlen($jwt) > 8192) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        $parts = explode('.', $jwt);
        if (count($parts) !== 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        $header = self::jsonObject(Pkce::base64UrlDecode($parts[0]));
        $claims = self::jsonObject(Pkce::base64UrlDecode($parts[1]));

        if (($header['alg'] ?? null) !== 'RS256' || isset($header['crit'])) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        if (isset($header['typ']) && strcasecmp((string) $header['typ'], 'JWT') !== 0) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        $kid = $header['kid'] ?? null;
        if (! is_string($kid) || $kid === '') {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        $pem = self::pem(self::matchingKey($jwks, $kid));
        $signature = Pkce::base64UrlDecode($parts[2]);
        $verified = openssl_verify($parts[0].'.'.$parts[1], $signature, $pem, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        self::assertClaims($claims, $issuer, $audience, $nonce, $code, $now ?? time(), $leeway);

        return $claims;
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private static function assertClaims(
        array $claims,
        string $issuer,
        string $audience,
        string $nonce,
        ?string $code,
        int $now,
        int $leeway,
    ): void {
        $tokenIssuer = $claims['iss'] ?? null;
        if (! is_string($tokenIssuer) || self::normalizeIssuer($tokenIssuer) !== self::normalizeIssuer($issuer)) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        if (strlen($tokenIssuer) > 255) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        $aud = $claims['aud'] ?? null;
        $audiences = is_string($aud) ? [$aud] : (is_array($aud) ? $aud : []);
        $audiences = array_values(array_filter($audiences, 'is_string'));
        if (! in_array($audience, $audiences, true)) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        if (count($audiences) > 1 && ($claims['azp'] ?? null) !== $audience) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        if (isset($claims['azp']) && $claims['azp'] !== $audience) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        if (! is_int($claims['exp'] ?? null) || $now > $claims['exp'] + $leeway) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        if (isset($claims['nbf']) && (! is_int($claims['nbf']) || $now + $leeway < $claims['nbf'])) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        if (isset($claims['iat']) && (! is_int($claims['iat']) || $claims['iat'] > $now + $leeway)) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        $tokenNonce = $claims['nonce'] ?? null;
        if (! is_string($tokenNonce) || ! hash_equals($nonce, $tokenNonce)) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        $sub = $claims['sub'] ?? null;
        if (! is_string($sub) || trim($sub) === '' || strlen($sub) > 255) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        if ($code !== null && isset($claims['c_hash'])) {
            if (! is_string($claims['c_hash'])) {
                throw new OidcException('Kimlik jetonu doğrulanamadı.');
            }
            $half = substr(hash('sha256', $code, true), 0, 16);
            $given = Pkce::base64UrlDecode($claims['c_hash']);
            if (strlen($given) !== strlen($half) || ! hash_equals($half, $given)) {
                throw new OidcException('Kimlik jetonu doğrulanamadı.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $jwks
     * @return array<string, mixed>
     */
    private static function matchingKey(array $jwks, string $kid): array
    {
        $keys = $jwks['keys'] ?? null;
        if (! is_array($keys)) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        foreach ($keys as $key) {
            if (! is_array($key) || ($key['kid'] ?? null) !== $kid || ($key['kty'] ?? null) !== 'RSA') {
                continue;
            }
            if (isset($key['use']) && $key['use'] !== 'sig') {
                continue;
            }
            if (isset($key['alg']) && $key['alg'] !== 'RS256') {
                continue;
            }
            if (! is_string($key['n'] ?? null) || ! is_string($key['e'] ?? null)) {
                continue;
            }

            return $key;
        }

        throw new OidcException('Kimlik jetonu doğrulanamadı.');
    }

    /**
     * @param  array<string, mixed>  $jwk
     */
    private static function pem(array $jwk): string
    {
        $modulus = self::asn1Integer(Pkce::base64UrlDecode((string) $jwk['n']));
        $exponent = self::asn1Integer(Pkce::base64UrlDecode((string) $jwk['e']));
        $rsa = self::asn1Sequence($modulus.$exponent);
        $algorithm = hex2bin('300d06092a864886f70d0101010500');
        if ($algorithm === false) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }
        $bitString = "\x03".self::asn1Length(strlen($rsa) + 1)."\x00".$rsa;
        $der = self::asn1Sequence($algorithm.$bitString);
        $pem = "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PUBLIC KEY-----\n";

        if (openssl_pkey_get_public($pem) === false) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        return $pem;
    }

    private static function asn1Integer(string $bytes): string
    {
        if ($bytes !== '' && (ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00".$bytes;
        }

        return "\x02".self::asn1Length(strlen($bytes)).$bytes;
    }

    private static function asn1Sequence(string $bytes): string
    {
        return "\x30".self::asn1Length(strlen($bytes)).$bytes;
    }

    private static function asn1Length(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $encoded = ltrim(pack('N', $length), "\x00");

        return chr(0x80 | strlen($encoded)).$encoded;
    }

    /**
     * @return array<string, mixed>
     */
    private static function jsonObject(string $json): array
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        return $decoded;
    }

    public static function normalizeIssuer(string $issuer): string
    {
        return rtrim(trim($issuer), '/');
    }
}
