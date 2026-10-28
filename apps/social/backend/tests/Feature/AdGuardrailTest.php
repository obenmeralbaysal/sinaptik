<?php

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdChangeLog;
use App\Models\AdSet;
use App\Models\Plan;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\WorkspaceUser;
use App\Support\Role;
use App\Support\WorkspaceContext;
use Tests\Support\World;

function adSetFor(World $world, int $budget = 10000): AdSet
{
    app(WorkspaceContext::class)->activate($world->workspace->id);
    $account = SocialAccount::query()->create([
        'workspace_id' => $world->workspace->id,
        'platform' => 'meta_ad_account',
        'external_id' => 'act_'.$world->workspace->id,
        'name' => 'Ads',
        'access_token' => 'token',
        'status' => 'connected',
    ]);
    $adAccount = AdAccount::query()->create([
        'workspace_id' => $world->workspace->id,
        'social_account_id' => $account->id,
        'external_id' => 'act_'.$world->workspace->id,
        'name' => 'Ads',
    ]);
    $campaign = AdCampaign::query()->create([
        'workspace_id' => $world->workspace->id,
        'ad_account_id' => $adAccount->id,
        'external_id' => 'cmp_'.$world->workspace->id,
        'name' => 'Kampanya',
    ]);

    return AdSet::query()->create([
        'workspace_id' => $world->workspace->id,
        'ad_campaign_id' => $campaign->id,
        'external_id' => 'set_'.$world->workspace->id,
        'name' => 'Set',
        'daily_budget_minor' => $budget,
        'status' => 'ACTIVE',
    ]);
}

it('rejects increases above the percent cap, cooldown, and kill switch', function () {
    $world = World::boot();
    $adSet = adSetFor($world);

    $this->actingAs($world->owner)->postJson('/api/ads/proposals', [
        'ad_set_id' => $adSet->id,
        'kind' => 'budget',
        'daily_budget_minor' => 20000,
    ])->assertStatus(422);

    $proposal = $this->actingAs($world->owner)->postJson('/api/ads/proposals', [
        'ad_set_id' => $adSet->id,
        'kind' => 'budget',
        'daily_budget_minor' => 11000,
    ])->assertCreated()->json('proposal');

    $this->actingAs($world->owner)->postJson('/api/ads/proposals/'.$proposal['id'].'/approve')->assertOk();
    $this->actingAs($world->owner)->postJson('/api/ads/proposals/'.$proposal['id'].'/apply')->assertOk();
    expect(AdChangeLog::query()->count())->toBe(1);

    $this->actingAs($world->owner)->postJson('/api/ads/proposals', [
        'ad_set_id' => $adSet->id,
        'kind' => 'budget',
        'daily_budget_minor' => 12000,
    ])->assertStatus(422);

    $world->workspace->update(['ads_kill_switch' => true, 'ads_cooldown_hours' => 0]);
    $this->actingAs($world->owner)->postJson('/api/ads/proposals', [
        'ad_set_id' => $adSet->fresh()->id,
        'kind' => 'status',
        'status' => 'PAUSED',
    ])->assertStatus(422);
});

it('lets only the owner enable auto apply inside plan caps', function () {
    $world = World::boot();
    $admin = User::factory()->create(['current_workspace_id' => $world->workspace->id]);
    WorkspaceUser::query()->create([
        'workspace_id' => $world->workspace->id,
        'user_id' => $admin->id,
        'role' => Role::ADMIN,
        'ads_write' => true,
    ]);

    $this->actingAs($admin)->patchJson('/api/workspace/ads-settings', ['ads_auto_apply' => true])->assertForbidden();
    $this->actingAs($world->owner)->patchJson('/api/workspace/ads-settings', ['ads_auto_apply' => true])->assertStatus(422);

    $world->switchPlan('agency');
    $this->actingAs($world->owner)->patchJson('/api/workspace/ads-settings', [
        'ads_auto_apply' => true,
        'ads_cooldown_hours' => 24,
    ])->assertOk();

    $adSet = adSetFor($world);
    $this->actingAs($world->owner)->postJson('/api/ads/proposals', [
        'ad_set_id' => $adSet->id,
        'kind' => 'budget',
        'daily_budget_minor' => 11000,
        'auto' => true,
    ])->assertCreated()->assertJsonPath('proposal.status', 'applied');
});
