<?php

namespace Tests\Feature;

use App\Identity\Oidc\OidcSession;
use App\Identity\Oidc\Pkce;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\OidcTokens;
use Tests\TestCase;

class OidcAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirects_with_pkce_and_callback_mirrors_the_subject(): void
    {
        $key = OidcTokens::key();
        $jwt = '';
        $posted = null;

        Http::fake(function ($request) use (&$jwt, &$posted, $key) {
            $url = $request->url();
            if (str_ends_with($url, '/.well-known/openid-configuration')) {
                return Http::response([
                    'issuer' => 'https://issuer.test',
                    'authorization_endpoint' => 'https://issuer.test/oauth/v2/authorize',
                    'token_endpoint' => 'https://issuer.test/oauth/v2/token',
                    'jwks_uri' => 'https://issuer.test/oauth/v2/keys',
                ]);
            }
            if (str_ends_with($url, '/oauth/v2/keys')) {
                return Http::response(['keys' => [$key['jwk']]]);
            }
            if (str_ends_with($url, '/oauth/v2/token')) {
                $posted = $request->data();

                return Http::response([
                    'token_type' => 'Bearer',
                    'expires_in' => 300,
                    'id_token' => $jwt,
                ]);
            }

            return Http::response('missing', 404);
        });

        $this->configureOidc();

        $login = $this->get('/auth/login')->assertRedirect();
        $location = (string) $login->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $verifier = session(OidcSession::CODE_VERIFIER);
        $this->assertIsString($verifier);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(Pkce::challengeS256($verifier), $query['code_challenge']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame(session(OidcSession::STATE), $query['state']);
        $this->assertSame(session(OidcSession::NONCE), $query['nonce']);
        $this->assertSame('google-idp', $query['idps']);
        $this->assertStringNotContainsString('test-client-secret', $location);
        $this->assertStringNotContainsString('code_verifier', $location);

        $code = 'splendid-code';
        $half = substr(hash('sha256', $code, true), 0, 16);
        $now = time();
        $jwt = OidcTokens::jwt($key['pem'], [
            'iss' => 'https://issuer.test',
            'sub' => 'subject-123',
            'aud' => 'analytics-web',
            'exp' => $now + 600,
            'iat' => $now,
            'nonce' => session(OidcSession::NONCE),
            'email' => 'ada@example.com',
            'email_verified' => true,
            'name' => 'Ada Lovelace',
            'amr' => ['pwd', 'otp'],
            'c_hash' => Pkce::base64UrlEncode($half),
        ]);

        $callback = $this->get('/auth/callback?'.http_build_query([
            'code' => $code,
            'state' => session(OidcSession::STATE),
        ]));

        $callback->assertRedirect('http://127.0.0.1:3027/');
        $this->assertStringNotContainsString('id_token', (string) $callback->headers->get('Location'));
        $this->assertAuthenticated();
        $this->assertSame(['pwd', 'otp'], session(OidcSession::AMR));
        $this->assertSame($verifier, $posted['code_verifier'] ?? null);
        $this->assertSame('authorization_code', $posted['grant_type'] ?? null);
        $this->assertSame('test-client-secret', $posted['client_secret'] ?? null);
        $this->assertArrayNotHasKey('code_verifier', $query);

        $user = User::query()->firstOrFail();
        $this->assertSame('subject-123', $user->external_subject_id);
        $this->assertSame('https://issuer.test', $user->issuer);
        $this->assertSame('ada@example.com', $user->email);
        $this->assertSame('Ada Lovelace', $user->name);
        $this->assertNull($user->password);
        $this->assertSame('owner', $user->workspaces()->first()?->pivot?->role);
        $this->assertSame(1, User::query()->count());
    }

    public function test_callback_rejects_a_state_mismatch_without_calling_the_issuer(): void
    {
        Http::fake([
            'https://issuer.test/.well-known/openid-configuration' => Http::response([
                'issuer' => 'https://issuer.test',
                'authorization_endpoint' => 'https://issuer.test/oauth/v2/authorize',
                'token_endpoint' => 'https://issuer.test/oauth/v2/token',
                'jwks_uri' => 'https://issuer.test/oauth/v2/keys',
            ]),
        ]);
        $this->configureOidc();
        $this->get('/auth/login')->assertRedirect();

        Http::fake();
        Http::preventStrayRequests();

        $this->get('/auth/callback?code=abc&state=not-the-state')
            ->assertStatus(400)
            ->assertJsonPath('message', 'Oturum doğrulaması başarısız.');
        $this->assertSame(0, User::query()->count());
    }

    public function test_callback_does_not_echo_provider_errors(): void
    {
        $this->configureOidc();

        $response = $this->get('/auth/callback?error=access_denied&error_description=SECRET_SENTINEL');

        $response->assertStatus(400);
        $this->assertStringNotContainsString('SECRET_SENTINEL', (string) $response->getContent());
    }

    public function test_password_login_is_closed_for_the_oidc_driver(): void
    {
        $this->configureOidc();

        $this->postJson('/api/login', [
            'email' => 'ada@example.com',
            'password' => 'password123',
        ])->assertStatus(501);
    }

    public function test_oidc_routes_are_hidden_for_the_local_driver(): void
    {
        config(['identity.driver' => 'local']);

        $this->get('/auth/login')->assertNotFound();
        $this->get('/auth/callback')->assertNotFound();
    }

    private function configureOidc(): void
    {
        config([
            'identity.driver' => 'oidc',
            'identity.oidc.issuer' => 'https://issuer.test',
            'identity.oidc.client_id' => 'analytics-web',
            'identity.oidc.client_secret' => 'test-client-secret',
            'identity.oidc.redirect_uri' => 'http://localhost/auth/callback',
            'identity.oidc.idp_hint' => 'google-idp',
            'identity.oidc.post_login_redirect' => 'http://127.0.0.1:3027/',
        ]);
    }
}
