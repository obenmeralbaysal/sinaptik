<?php

namespace Tests\Unit;

use App\Contracts\IdentityProvider;
use App\Identity\LocalIdentityProvider;
use App\Identity\OidcIdentityProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentityProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_adapter_issues_a_subject_and_authenticates(): void
    {
        config(['identity.driver' => 'local']);

        $provider = app(IdentityProvider::class);
        $this->assertInstanceOf(LocalIdentityProvider::class, $provider);

        $this->startSession();

        $user = $provider->register([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password123',
            'workspace_name' => 'Analitik',
        ]);

        $this->assertNotSame('', $user->external_subject_id);
        $this->assertSame('local', $user->issuer);
        $this->assertTrue(User::query()->whereKey($user->id)->exists());

        $provider->logout();
        $this->assertNull($provider->currentUser());

        $authed = $provider->authenticate([
            'email' => 'ada@example.com',
            'password' => 'password123',
        ]);

        $this->assertTrue($authed->is($user));
        $this->assertTrue($provider->currentUser()?->is($user));
    }

    public function test_oidc_driver_binds_the_stub(): void
    {
        config(['identity.driver' => 'oidc']);

        $provider = app(IdentityProvider::class);

        $this->assertInstanceOf(OidcIdentityProvider::class, $provider);
        $this->assertNull($provider->currentUser());
    }
}
