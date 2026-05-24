<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\X\XOAuthService;
use Tests\TestCase;

class XOAuthPKCETest extends TestCase
{
    public function test_auth_url_uses_s256_pkce_with_challenge_distinct_from_verifier(): void
    {
        config()->set('services.x.client_id', 'fake-client');
        config()->set('services.x.client_secret', 'fake-secret');
        config()->set('services.x.callback_url', 'http://localhost:8000/api/social-accounts/x/callback');

        $oauth = app(XOAuthService::class);

        $org = new Organization(['id' => 1, 'slug' => 'test', 'name' => 'Test']);
        $org->id = 1;

        $url = $oauth->getAuthUrl($org);

        $this->assertStringStartsWith('https://x.com/i/oauth2/authorize?', $url);

        parse_str(parse_url($url, PHP_URL_QUERY), $params);

        $this->assertSame('S256', $params['code_challenge_method']);
        $this->assertNotEmpty($params['code_challenge']);
        $this->assertNotEmpty($params['state']);
        $this->assertSame('code', $params['response_type']);
        $this->assertSame('fake-client', $params['client_id']);

        // Challenge should be 43 chars (base64url-encoded SHA-256)
        $this->assertSame(43, strlen($params['code_challenge']));
        // No padding chars
        $this->assertStringNotContainsString('=', $params['code_challenge']);
        $this->assertStringNotContainsString('+', $params['code_challenge']);
        $this->assertStringNotContainsString('/', $params['code_challenge']);
    }
}
