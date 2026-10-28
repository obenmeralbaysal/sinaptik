<?php

namespace App\Identity\Oidc;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class OidcClient
{
    public function ensureConfigured(): void
    {
        if ($this->issuer() === '' || $this->clientId() === '') {
            throw new OidcException('OIDC yapılandırması eksik.', 503);
        }

        $this->redirectUri();
    }

    public function authorizeUrl(string $state, string $nonce, string $codeChallenge): string
    {
        $this->ensureConfigured();
        $discovery = $this->discovery();
        $endpoint = (string) $discovery['authorization_endpoint'];
        $this->assertEndpoint($endpoint, (string) $discovery['issuer']);

        $query = [
            'response_type' => 'code',
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'scope' => $this->scopes(),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];

        $hint = $this->idpHint();
        if ($hint !== '') {
            $query['idps'] = $hint;
        }

        $separator = str_contains($endpoint, '?') ? '&' : '?';

        return $endpoint.$separator.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array<string, mixed>
     */
    public function redeem(string $code, string $verifier, string $nonce): array
    {
        $this->ensureConfigured();
        if (strlen($code) < 1 || strlen($code) > 2048) {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        $discovery = $this->discovery();
        $tokenEndpoint = (string) $discovery['token_endpoint'];
        $this->assertEndpoint($tokenEndpoint, (string) $discovery['issuer']);

        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
            'client_id' => $this->clientId(),
            'code_verifier' => $verifier,
        ];

        $secret = trim((string) config('identity.oidc.client_secret'));
        if ($secret !== '') {
            $params['client_secret'] = $secret;
        }

        try {
            $response = $this->http()->asForm()->post($tokenEndpoint, $params);
        } catch (\Throwable) {
            throw new OidcException('Kimlik sağlayıcıya ulaşılamadı.', 502);
        }

        $body = $response->json();
        $idToken = is_array($body) ? ($body['id_token'] ?? null) : null;
        if (! $response->successful() || ! is_string($idToken) || $idToken === '') {
            throw new OidcException('Kimlik jetonu doğrulanamadı.');
        }

        $jwks = $this->jwks((string) $discovery['jwks_uri'], (string) $discovery['issuer']);

        return IdTokenVerifier::verify(
            $idToken,
            $jwks,
            (string) $discovery['issuer'],
            $this->clientId(),
            $nonce,
            $code,
            time(),
            (int) config('identity.oidc.leeway', 60),
        );
    }

    public function postLoginUrl(): string
    {
        $target = trim((string) config('identity.oidc.post_login_redirect'));
        $parts = parse_url($target);
        $scheme = is_array($parts) ? ($parts['scheme'] ?? '') : '';
        if (! in_array($scheme, ['http', 'https'], true) || empty($parts['host'])) {
            return rtrim((string) config('app.url'), '/').'/';
        }

        return $target;
    }

    /**
     * @return array<string, mixed>
     */
    private function discovery(): array
    {
        $issuer = $this->issuer();

        /** @var array<string, mixed> $document */
        $document = Cache::remember($this->cacheKey('discovery'), 600, function () use ($issuer) {
            $url = $issuer.'/.well-known/openid-configuration';
            try {
                $response = $this->http()->get($url);
            } catch (\Throwable) {
                throw new OidcException('Kimlik sağlayıcıya ulaşılamadı.', 502);
            }

            $body = $response->json();
            if (! $response->successful() || ! is_array($body)) {
                throw new OidcException('Kimlik sağlayıcı yapılandırması doğrulanamadı.');
            }

            foreach (['issuer', 'authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $key) {
                if (! is_string($body[$key] ?? null) || $body[$key] === '') {
                    throw new OidcException('Kimlik sağlayıcı yapılandırması doğrulanamadı.');
                }
            }

            if (IdTokenVerifier::normalizeIssuer($body['issuer']) !== $issuer) {
                throw new OidcException('Kimlik sağlayıcı yapılandırması doğrulanamadı.');
            }

            return [
                'issuer' => $body['issuer'],
                'authorization_endpoint' => $body['authorization_endpoint'],
                'token_endpoint' => $body['token_endpoint'],
                'jwks_uri' => $body['jwks_uri'],
            ];
        });

        return $document;
    }

    /**
     * @return array<string, mixed>
     */
    private function jwks(string $url, string $issuer): array
    {
        $this->assertEndpoint($url, $issuer);

        /** @var array<string, mixed> $jwks */
        $jwks = Cache::remember($this->cacheKey('jwks'), 600, function () use ($url) {
            try {
                $response = $this->http()->get($url);
            } catch (\Throwable) {
                throw new OidcException('Kimlik sağlayıcıya ulaşılamadı.', 502);
            }

            $body = $response->json();
            if (! $response->successful() || ! is_array($body) || ! is_array($body['keys'] ?? null)) {
                throw new OidcException('Kimlik jetonu doğrulanamadı.');
            }

            return ['keys' => $body['keys']];
        });

        return $jwks;
    }

    private function assertEndpoint(string $url, string $issuer): void
    {
        $endpoint = parse_url($url);
        $base = parse_url($issuer);
        if (! is_array($endpoint) || ! is_array($base)) {
            throw new OidcException('Kimlik sağlayıcı yapılandırması doğrulanamadı.');
        }

        $endpointPort = $endpoint['port'] ?? (($endpoint['scheme'] ?? '') === 'https' ? 443 : 80);
        $basePort = $base['port'] ?? (($base['scheme'] ?? '') === 'https' ? 443 : 80);
        $host = (string) ($endpoint['host'] ?? '');
        if (
            ($endpoint['scheme'] ?? '') !== ($base['scheme'] ?? '')
            || $host !== (string) ($base['host'] ?? '')
            || $endpointPort !== $basePort
        ) {
            throw new OidcException('Kimlik sağlayıcı yapılandırması doğrulanamadı.');
        }

        $scheme = (string) ($endpoint['scheme'] ?? '');
        $local = in_array($host, ['127.0.0.1', 'localhost'], true);
        if ($scheme === 'https' || ($scheme === 'http' && $local)) {
            return;
        }

        throw new OidcException('Kimlik sağlayıcı yapılandırması doğrulanamadı.');
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout(5)
            ->connectTimeout(3)
            ->withOptions(['allow_redirects' => false]);
    }

    private function issuer(): string
    {
        return IdTokenVerifier::normalizeIssuer((string) config('identity.oidc.issuer'));
    }

    private function clientId(): string
    {
        return trim((string) config('identity.oidc.client_id'));
    }

    private function redirectUri(): string
    {
        $configured = trim((string) config('identity.oidc.redirect_uri'));
        $uri = $configured !== '' ? $configured : rtrim((string) config('app.url'), '/').'/auth/callback';
        $parts = parse_url($uri);
        $scheme = is_array($parts) ? ($parts['scheme'] ?? '') : '';
        if (! in_array($scheme, ['http', 'https'], true) || empty($parts['host'])) {
            throw new OidcException('OIDC yapılandırması eksik.', 503);
        }

        return $uri;
    }

    private function scopes(): string
    {
        $scopes = trim((string) config('identity.oidc.scopes', 'openid profile email'));
        if ($scopes === '') {
            $scopes = 'openid profile email';
        }
        if (! preg_match('/(^|\s)openid(\s|$)/', $scopes)) {
            $scopes = 'openid '.$scopes;
        }

        return $scopes;
    }

    private function idpHint(): string
    {
        $hint = trim((string) config('identity.oidc.idp_hint'));
        if ($hint === '') {
            return '';
        }
        if (preg_match('/[\s#&?=]/', $hint) === 1 || strlen($hint) > 200) {
            throw new OidcException('OIDC yapılandırması eksik.', 503);
        }

        return $hint;
    }

    private function cacheKey(string $kind): string
    {
        return 'oidc.'.$kind.'.'.hash('sha256', $this->issuer());
    }
}
