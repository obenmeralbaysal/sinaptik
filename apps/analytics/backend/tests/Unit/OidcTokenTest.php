<?php

namespace Tests\Unit;

use App\Identity\MfaAssurance;
use App\Identity\Oidc\IdTokenVerifier;
use App\Identity\Oidc\OidcException;
use App\Identity\Oidc\Pkce;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\OidcTokens;
use Tests\TestCase;

class OidcTokenTest extends TestCase
{
    public function test_pkce_s256_matches_the_rfc_vector(): void
    {
        $verifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';

        $this->assertSame('E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM', Pkce::challengeS256($verifier));
    }

    public function test_id_token_round_trip_and_rejections(): void
    {
        $key = OidcTokens::key();
        $now = time();
        $code = 'auth-code';
        $claims = $this->claims($now, $code);
        $jwt = OidcTokens::jwt($key['pem'], $claims);

        $verified = IdTokenVerifier::verify($jwt, ['keys' => [$key['jwk']]], 'https://issuer.test', 'analytics-web', 'nonce-1', $code, $now);

        $this->assertSame('subject-123', $verified['sub']);
        $this->assertSame('https://issuer.test', $verified['iss']);

        $parts = explode('.', $jwt);
        $parts[2][8] = $parts[2][8] === 'A' ? 'B' : 'A';
        $tampered = implode('.', $parts);
        $this->assertRejected($tampered, $key['jwk'], $claims['nonce'], $code, $now);

        $none = Pkce::base64UrlEncode('{"alg":"none","typ":"JWT"}').'.'.Pkce::base64UrlEncode('{"sub":"x"}').'.aa';
        $this->assertRejected($none, $key['jwk'], 'nonce-1', null, $now);

        $wrongNonce = OidcTokens::jwt($key['pem'], $this->claims($now, $code, ['nonce' => 'other']));
        $this->assertRejected($wrongNonce, $key['jwk'], 'nonce-1', $code, $now);

        $expired = OidcTokens::jwt($key['pem'], $this->claims($now, $code, ['exp' => $now - 120]));
        $this->assertRejected($expired, $key['jwk'], 'nonce-1', $code, $now);

        $wrongAud = OidcTokens::jwt($key['pem'], $this->claims($now, $code, ['aud' => 'other-app']));
        $this->assertRejected($wrongAud, $key['jwk'], 'nonce-1', $code, $now);

        $badHash = OidcTokens::jwt($key['pem'], $this->claims($now, $code, ['c_hash' => 'aaaa']));
        $this->assertRejected($badHash, $key['jwk'], 'nonce-1', $code, $now);
    }

    #[DataProvider('amrProvider')]
    public function test_privileged_amr_values(array $amr, bool $ok): void
    {
        $this->assertSame($ok, MfaAssurance::amrSatisfies($amr));
    }

    public static function amrProvider(): array
    {
        return [
            'otp' => [['otp'], true],
            'mfa upper' => [['OTP', 'MFA'], true],
            'mfa only' => [['mfa'], true],
            'password' => [['pwd'], false],
            'prefix' => [['mfax'], false],
            'empty' => [[], false],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function claims(int $now, string $code, array $overrides = []): array
    {
        $half = substr(hash('sha256', $code, true), 0, 16);

        return array_replace([
            'iss' => 'https://issuer.test',
            'sub' => 'subject-123',
            'aud' => 'analytics-web',
            'exp' => $now + 600,
            'iat' => $now,
            'nonce' => 'nonce-1',
            'c_hash' => Pkce::base64UrlEncode($half),
        ], $overrides);
    }

    private function assertRejected(string $jwt, array $jwk, string $nonce, ?string $code, int $now): void
    {
        try {
            IdTokenVerifier::verify($jwt, ['keys' => [$jwk]], 'https://issuer.test', 'analytics-web', $nonce, $code, $now);
            $this->fail('token was accepted');
        } catch (OidcException $e) {
            $this->assertSame('Kimlik jetonu doğrulanamadı.', $e->getMessage());
        }
    }
}
