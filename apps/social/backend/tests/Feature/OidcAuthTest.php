<?php

use App\Contracts\IdentityProvider;
use App\Identity\LocalIdentityProvider;
use App\Identity\Oidc\OidcSession;
use App\Identity\Oidc\Pkce;
use App\Identity\OidcIdentityProvider;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Http;
use Tests\Support\OidcTokens;

it('redirects login with pkce and mirrors the subject on callback', function () {
    (new DatabaseSeeder)->run();
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

    configureOidc();

    $login = $this->get('/auth/login')->assertRedirect();
    $location = (string) $login->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    $verifier = session(OidcSession::CODE_VERIFIER);
    expect($verifier)->toBeString();
    expect($query['code_challenge_method'])->toBe('S256');
    expect($query['code_challenge'])->toBe(Pkce::challengeS256($verifier));
    expect($query['response_type'])->toBe('code');
    expect($query['state'])->toBe(session(OidcSession::STATE));
    expect($query['nonce'])->toBe(session(OidcSession::NONCE));
    expect($query['idps'])->toBe('google-idp');
    expect($location)->not->toContain('test-client-secret');
    expect($location)->not->toContain('code_verifier');

    $code = 'splendid-code';
    $half = substr(hash('sha256', $code, true), 0, 16);
    $now = time();
    $jwt = OidcTokens::jwt($key['pem'], [
        'iss' => 'https://issuer.test',
        'sub' => 'subject-123',
        'aud' => 'social-web',
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

    $callback->assertRedirect('http://127.0.0.1:3021/uygulama');
    expect((string) $callback->headers->get('Location'))->not->toContain('id_token');
    $this->assertAuthenticated();
    expect(session(OidcSession::AMR))->toBe(['pwd', 'otp']);
    expect($posted['code_verifier'] ?? null)->toBe($verifier);
    expect($posted['grant_type'] ?? null)->toBe('authorization_code');
    expect($posted['client_secret'] ?? null)->toBe('test-client-secret');
    expect($query)->not->toHaveKey('code_verifier');

    $user = User::query()->where('external_subject_id', 'subject-123')->firstOrFail();
    expect($user->issuer)->toBe('https://issuer.test');
    expect($user->email)->toBe('ada@example.com');
    expect($user->name)->toBe('Ada Lovelace');
    expect($user->password)->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->workspaces()->first()?->pivot?->role)->toBe('owner');
    expect(User::query()->where('external_subject_id', 'subject-123')->count())->toBe(1);
});

it('rejects a state mismatch without calling the issuer', function () {
    Http::fake([
        'https://issuer.test/.well-known/openid-configuration' => Http::response([
            'issuer' => 'https://issuer.test',
            'authorization_endpoint' => 'https://issuer.test/oauth/v2/authorize',
            'token_endpoint' => 'https://issuer.test/oauth/v2/token',
            'jwks_uri' => 'https://issuer.test/oauth/v2/keys',
        ]),
    ]);
    configureOidc();
    $this->get('/auth/login')->assertRedirect();

    Http::fake();
    Http::preventStrayRequests();

    $this->get('/auth/callback?code=abc&state=not-the-state')
        ->assertStatus(400)
        ->assertJsonPath('message', 'Oturum doğrulaması başarısız.');
    expect(User::query()->count())->toBe(0);
});

it('does not echo provider errors', function () {
    configureOidc();

    $response = $this->get('/auth/callback?error=access_denied&error_description=SECRET_SENTINEL');

    $response->assertStatus(400);
    expect((string) $response->getContent())->not->toContain('SECRET_SENTINEL');
});

it('closes password login for the oidc driver', function () {
    configureOidc();

    $this->postJson('/api/login', [
        'email' => 'ada@example.com',
        'password' => 'password123',
    ])->assertStatus(501);

    $this->postJson('/api/register', [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => 'password123',
    ])->assertStatus(501);
});

it('hides oidc routes for the local driver', function () {
    config(['identity.driver' => 'local']);

    $this->get('/auth/login')->assertNotFound();
    $this->get('/auth/callback')->assertNotFound();
    $this->getJson('/api/auth/identity')
        ->assertOk()
        ->assertJsonPath('driver', 'local')
        ->assertJsonPath('login_url', null);
});

it('binds the identity provider from the driver', function () {
    config(['identity.driver' => 'local']);
    expect(app(IdentityProvider::class))->toBeInstanceOf(LocalIdentityProvider::class);

    config(['identity.driver' => 'oidc']);
    expect(app(IdentityProvider::class))->toBeInstanceOf(OidcIdentityProvider::class);
});

function configureOidc(): void
{
    config([
        'identity.driver' => 'oidc',
        'identity.oidc.issuer' => 'https://issuer.test',
        'identity.oidc.client_id' => 'social-web',
        'identity.oidc.client_secret' => 'test-client-secret',
        'identity.oidc.redirect_uri' => 'http://localhost/auth/callback',
        'identity.oidc.idp_hint' => 'google-idp',
        'identity.oidc.post_login_redirect' => 'http://127.0.0.1:3021/uygulama',
    ]);
}
