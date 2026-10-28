<?php

namespace App\Identity;

use App\Contracts\IdentityProvider;
use App\Identity\Oidc\OidcClient;
use App\Identity\Oidc\OidcException;
use App\Identity\Oidc\OidcSession;
use App\Identity\Oidc\Pkce;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class OidcIdentityProvider implements IdentityProvider
{
    public function __construct(
        private readonly OidcClient $client,
        private readonly IdentityMirror $mirror,
    ) {}

    public function currentUser(): ?User
    {
        $user = Auth::guard('web')->user();

        return $user instanceof User ? $user : null;
    }

    public function authenticate(array $credentials): User
    {
        throw new OidcException('Parola ile giriş bu kimlik sürücüsünde kapalı.');
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();

        if (session()->isStarted()) {
            session()->invalidate();
            session()->regenerateToken();
        }
    }

    public function authorizationUrl(): string
    {
        $this->client->ensureConfigured();
        $state = Pkce::randomToken();
        $nonce = Pkce::randomToken();
        $verifier = Pkce::verifier();

        session([
            OidcSession::STATE => $state,
            OidcSession::NONCE => $nonce,
            OidcSession::CODE_VERIFIER => $verifier,
        ]);

        return $this->client->authorizeUrl($state, $nonce, Pkce::challengeS256($verifier));
    }

    public function completeLogin(string $code, string $state): User
    {
        $expectedState = session()->pull(OidcSession::STATE);
        $nonce = session()->pull(OidcSession::NONCE);
        $verifier = session()->pull(OidcSession::CODE_VERIFIER);

        if (! is_string($expectedState) || ! hash_equals($expectedState, $state)) {
            throw new OidcException('Oturum doğrulaması başarısız.');
        }
        if (! is_string($nonce) || $nonce === '' || ! is_string($verifier) || strlen($verifier) < 43 || strlen($verifier) > 128) {
            throw new OidcException('Oturum doğrulaması başarısız.');
        }

        $claims = $this->client->redeem($code, $verifier, $nonce);
        $user = $this->mirror->upsert(
            (string) $claims['iss'],
            (string) $claims['sub'],
            is_string($claims['email'] ?? null) ? $claims['email'] : null,
            $this->displayName($claims),
            ($claims['email_verified'] ?? false) === true || ($claims['email_verified'] ?? false) === 'true',
        );

        Auth::guard('web')->login($user);
        session([OidcSession::AMR => $this->amr($claims['amr'] ?? [])]);

        return $user->fresh(['workspaces', 'currentWorkspace']) ?? $user;
    }

    public function postLoginUrl(): string
    {
        return $this->client->postLoginUrl();
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function displayName(array $claims): ?string
    {
        if (is_string($claims['name'] ?? null) && trim($claims['name']) !== '') {
            return trim($claims['name']);
        }

        $parts = [];
        foreach (['given_name', 'family_name'] as $key) {
            if (is_string($claims[$key] ?? null) && trim($claims[$key]) !== '') {
                $parts[] = trim($claims[$key]);
            }
        }

        return $parts === [] ? null : implode(' ', $parts);
    }

    /**
     * @return list<string>
     */
    private function amr(mixed $amr): array
    {
        if (is_string($amr)) {
            $amr = [$amr];
        }
        if (! is_array($amr)) {
            return [];
        }

        $values = [];
        foreach ($amr as $value) {
            if (is_string($value) && $value !== '' && strlen($value) <= 64) {
                $values[] = $value;
            }
        }

        return $values;
    }
}
