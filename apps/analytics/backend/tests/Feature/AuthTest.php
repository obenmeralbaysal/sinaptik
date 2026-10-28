<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_login_me_and_logout(): void
    {
        $registered = $this->withHeader('Origin', 'http://localhost')->postJson('/api/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'workspace_name' => 'Analitik',
        ])->assertCreated();

        $registered->assertJsonPath('user.email', 'ada@example.com');
        $registered->assertJsonPath('user.issuer', 'local');
        $this->assertNotSame('', (string) $registered->json('user.external_subject_id'));
        $this->assertSame('analitik', $registered->json('user.current_workspace.slug'));

        $this->withHeader('Origin', 'http://localhost')->postJson('/api/logout')->assertOk();

        $this->withHeader('Origin', 'http://localhost')->postJson('/api/login', [
            'email' => 'ada@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable();

        $this->withHeader('Origin', 'http://localhost')->postJson('/api/login', [
            'email' => 'ada@example.com',
            'password' => 'password123',
        ])->assertOk()->assertJsonPath('user.email', 'ada@example.com');

        $this->withHeader('Origin', 'http://localhost')->getJson('/api/me')->assertOk()->assertJsonPath('user.issuer', 'local');

        $this->assertSame(1, User::query()->count());
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }
}
