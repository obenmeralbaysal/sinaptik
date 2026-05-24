<?php

namespace App\Services\X;

use App\Models\Organization;
use App\Models\SocialAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class XOAuthService
{
    private const AUTH_URL = 'https://x.com/i/oauth2/authorize';
    private const TOKEN_URL = 'https://api.twitter.com/2/oauth2/token';
    private const ME_URL = 'https://api.twitter.com/2/users/me';

    public function getAuthUrl(Organization $organization): string
    {
        $clientId = $this->clientId();

        $verifier = $this->generateCodeVerifier();
        $challenge = $this->generateCodeChallenge($verifier);

        // Bind state to organization for the callback
        $state = Str::uuid()->toString();

        Cache::put($this->stateKey($state), [
            'organization_id' => $organization->id,
            'verifier' => $verifier,
        ], now()->addMinutes(15));

        $params = [
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $this->callbackUrl(),
            'scope' => config('services.x.scopes', 'tweet.read tweet.write users.read offline.access'),
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ];

        return self::AUTH_URL.'?'.http_build_query($params);
    }

    public function exchangeCode(string $code, string $state): SocialAccount
    {
        $payload = Cache::pull($this->stateKey($state));
        if (! $payload) {
            throw new RuntimeException('Invalid or expired state.');
        }

        $organization = Organization::findOrFail($payload['organization_id']);
        $verifier = $payload['verifier'];

        $response = Http::asForm()
            ->withBasicAuth($this->clientId(), $this->clientSecret())
            ->post(self::TOKEN_URL, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->callbackUrl(),
                'code_verifier' => $verifier,
                'client_id' => $this->clientId(),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Token exchange failed: '.$response->body());
        }

        $tokens = $response->json();
        $expiresAt = isset($tokens['expires_in'])
            ? now()->addSeconds((int) $tokens['expires_in'])
            : null;

        // Fetch profile
        $me = Http::withToken($tokens['access_token'])
            ->get(self::ME_URL, ['user.fields' => 'profile_image_url,username,name']);

        $userData = $me->successful() ? ($me->json()['data'] ?? []) : [];

        $account = SocialAccount::withoutGlobalScopes()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'provider' => 'x',
                'provider_user_id' => $userData['id'] ?? null,
            ],
            [
                'username' => $userData['username'] ?? null,
                'display_name' => $userData['name'] ?? null,
                'avatar_url' => $userData['profile_image_url'] ?? null,
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'expires_at' => $expiresAt,
                'scopes' => $tokens['scope'] ?? null,
                'is_active' => true,
            ]
        );

        return $account;
    }

    public function refreshIfExpiring(SocialAccount $account): SocialAccount
    {
        if (! $account->refresh_token || ! $account->isExpired(60)) {
            return $account;
        }

        $response = Http::asForm()
            ->withBasicAuth($this->clientId(), $this->clientSecret())
            ->post(self::TOKEN_URL, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $account->refresh_token,
                'client_id' => $this->clientId(),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Token refresh failed: '.$response->body());
        }

        $tokens = $response->json();

        $account->access_token = $tokens['access_token'];
        if (! empty($tokens['refresh_token'])) {
            $account->refresh_token = $tokens['refresh_token'];
        }
        $account->expires_at = isset($tokens['expires_in'])
            ? now()->addSeconds((int) $tokens['expires_in'])
            : null;
        $account->save();

        return $account;
    }

    private function generateCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }

    private function generateCodeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    private function stateKey(string $state): string
    {
        return 'x_oauth_state:'.$state;
    }

    private function clientId(): string
    {
        $id = (string) config('services.x.client_id');
        if ($id === '') {
            throw new RuntimeException('X_CLIENT_ID is not configured.');
        }
        return $id;
    }

    private function clientSecret(): string
    {
        return (string) config('services.x.client_secret');
    }

    private function callbackUrl(): string
    {
        return (string) config('services.x.callback_url');
    }
}
