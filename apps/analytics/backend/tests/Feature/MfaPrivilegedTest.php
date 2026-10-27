<?php

namespace Tests\Feature;

use App\Identity\Oidc\OidcSession;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MfaPrivilegedTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_admin_require_mfa_or_otp_in_amr(): void
    {
        config(['identity.local_mfa' => false]);

        [$owner, $workspace] = $this->actor('owner');
        $this->callSites($owner, $workspace)->assertForbidden()->assertJsonPath('error', 'mfa_required');

        $this->callSites($owner, $workspace, ['pwd'])->assertForbidden()->assertJsonPath('error', 'mfa_required');
        $this->callSites($owner, $workspace, ['otp'])->assertStatus(402);
        $this->callSites($owner, $workspace, ['MFA'])->assertStatus(402);

        [$admin, $adminWorkspace] = $this->actor('admin');
        $this->callSites($admin, $adminWorkspace)->assertForbidden()->assertJsonPath('error', 'mfa_required');

        [$member, $memberWorkspace] = $this->actor('member');
        $this->callSites($member, $memberWorkspace)->assertStatus(402);
    }

    public function test_local_mfa_flag_satisfies_the_guard_only_for_the_local_driver(): void
    {
        config([
            'identity.driver' => 'local',
            'identity.local_mfa' => true,
        ]);

        [$owner, $workspace] = $this->actor('owner');
        $this->callSites($owner, $workspace)->assertStatus(402);

        config([
            'identity.driver' => 'oidc',
            'identity.local_mfa' => true,
        ]);

        $this->callSites($owner, $workspace)->assertForbidden()->assertJsonPath('error', 'mfa_required');
        $this->callSites($owner, $workspace, ['otp'])->assertStatus(402);
    }

    /**
     * @param  list<string>|null  $amr
     */
    private function callSites(User $user, Workspace $workspace, ?array $amr = null): TestResponse
    {
        return $this->actingAs($user)
            ->withHeader('X-Workspace-Slug', $workspace->slug)
            ->withSession([OidcSession::AMR => $amr ?? []])
            ->getJson('/api/sites');
    }

    /**
     * @return array{0: User, 1: Workspace}
     */
    private function actor(string $role): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'owner_id' => $user->id,
            'slug' => 'alan-'.$role,
        ]);
        $workspace->users()->attach($user->id, ['role' => $role]);
        $user->forceFill(['current_workspace_id' => $workspace->id])->save();

        return [$user, $workspace];
    }
}
