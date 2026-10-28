<?php

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdSet;
use App\Models\SocialAccount;
use App\Models\User;
use App\Support\Role;
use Illuminate\Support\Facades\Notification;
use Tests\Support\World;

it('enforces seat limits and accepts a single-use invite', function () {
    Notification::fake();
    $world = World::boot();
    $world->switchPlan('starter');

    $this->actingAs($world->owner)
        ->postJson('/api/invites', ['email' => 'editor@sinapp.test', 'role' => 'editor'])
        ->assertStatus(422);

    $world->switchPlan('pro');
    $created = $this->actingAs($world->owner)
        ->postJson('/api/invites', ['email' => 'editor@sinapp.test', 'role' => 'editor', 'ads_write' => true])
        ->assertCreated();

    $token = $created->json('token');
    $this->actingAs($world->owner)->deleteJson('/api/invites/'.$created->json('invite.id'))->assertOk();
    $editor = User::factory()->create(['email' => 'editor@sinapp.test']);
    $this->actingAs($editor)->postJson('/api/invites/accept', ['token' => $token])->assertStatus(422);

    $created = $this->actingAs($world->owner)
        ->postJson('/api/invites', ['email' => 'editor@sinapp.test', 'role' => 'editor', 'ads_write' => false])
        ->assertCreated();
    $this->actingAs($editor)->postJson('/api/invites/accept', ['token' => $created->json('token')])->assertOk();
    $this->actingAs($editor)->postJson('/api/invites/accept', ['token' => $created->json('token')])->assertStatus(422);
});

it('checks content and ads permissions', function () {
    $world = World::boot();
    $viewer = User::factory()->create(['current_workspace_id' => $world->workspace->id]);
    $editor = User::factory()->create(['current_workspace_id' => $world->workspace->id]);
    \App\Models\WorkspaceUser::query()->create([
        'workspace_id' => $world->workspace->id,
        'user_id' => $viewer->id,
        'role' => Role::VIEWER,
        'ads_write' => false,
    ]);
    \App\Models\WorkspaceUser::query()->create([
        'workspace_id' => $world->workspace->id,
        'user_id' => $editor->id,
        'role' => Role::EDITOR,
        'ads_write' => false,
    ]);

    $this->actingAs($viewer)->postJson('/api/posts', ['body' => 'merhaba', 'status' => 'draft'])->assertForbidden();
    $this->actingAs($editor)->postJson('/api/posts', ['body' => 'merhaba', 'status' => 'draft'])->assertCreated();

    $account = SocialAccount::query()->create([
        'workspace_id' => $world->workspace->id,
        'platform' => 'meta_ad_account',
        'external_id' => 'act_1',
        'name' => 'Ads',
        'access_token' => 'token',
        'status' => 'connected',
    ]);
    $adAccount = AdAccount::query()->create([
        'workspace_id' => $world->workspace->id,
        'social_account_id' => $account->id,
        'external_id' => 'act_1',
        'name' => 'Ads',
    ]);
    $campaign = AdCampaign::query()->create([
        'workspace_id' => $world->workspace->id,
        'ad_account_id' => $adAccount->id,
        'external_id' => 'cmp',
        'name' => 'Kampanya',
    ]);
    $adSet = AdSet::query()->create([
        'workspace_id' => $world->workspace->id,
        'ad_campaign_id' => $campaign->id,
        'external_id' => 'set',
        'name' => 'Set',
        'daily_budget_minor' => 10000,
        'status' => 'ACTIVE',
    ]);

    $this->actingAs($editor)->postJson('/api/ads/proposals', [
        'ad_set_id' => $adSet->id,
        'kind' => 'status',
        'status' => 'PAUSED',
    ])->assertForbidden();

    $editorMembership = \App\Models\WorkspaceUser::query()->where('user_id', $editor->id)->first();
    $editorMembership->update(['ads_write' => true]);

    $this->actingAs($editor)->postJson('/api/ads/proposals', [
        'ad_set_id' => $adSet->id,
        'kind' => 'status',
        'status' => 'PAUSED',
    ])->assertCreated();
});
