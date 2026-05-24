<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Target;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_scope_filters_by_current_organization(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $orgA = Organization::create(['slug' => 'a', 'name' => 'A', 'owner_id' => $userA->id]);
        $orgB = Organization::create(['slug' => 'b', 'name' => 'B', 'owner_id' => $userB->id]);
        $orgA->users()->attach($userA->id, ['role' => 'owner']);
        $orgB->users()->attach($userB->id, ['role' => 'owner']);

        Target::create([
            'organization_id' => $orgA->id,
            'target_username' => 'alice',
        ]);
        Target::create([
            'organization_id' => $orgB->id,
            'target_username' => 'bob',
        ]);

        // Bind orgA - we should only see alice
        app()->instance('currentOrganization', $orgA);
        $this->assertSame(1, Target::query()->count());
        $this->assertSame('alice', Target::query()->first()->target_username);

        // Bind orgB - we should only see bob
        app()->instance('currentOrganization', $orgB);
        $this->assertSame(1, Target::query()->count());
        $this->assertSame('bob', Target::query()->first()->target_username);

        // Without binding (e.g. a job using withoutGlobalScopes) - both visible
        app()->forgetInstance('currentOrganization');
        $this->assertSame(2, Target::withoutGlobalScopes()->count());
    }

    public function test_belongs_to_org_auto_fills_organization_id_on_create(): void
    {
        $user = User::factory()->create();
        $org = Organization::create(['slug' => 'auto', 'name' => 'Auto', 'owner_id' => $user->id]);
        $org->users()->attach($user->id, ['role' => 'owner']);

        app()->instance('currentOrganization', $org);

        $t = Target::create(['target_username' => 'charlie']);

        $this->assertEquals($org->id, $t->organization_id);
    }
}
