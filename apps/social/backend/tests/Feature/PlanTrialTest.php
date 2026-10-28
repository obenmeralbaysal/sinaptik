<?php

use App\Models\Post;
use App\Services\TrialService;
use App\Support\WorkspaceContext;
use Tests\Support\World;

it('moves an expired trial to read only and pauses scheduled posts', function () {
    $world = World::boot();
    app(WorkspaceContext::class)->activate($world->workspace->id);
    Post::query()->create([
        'workspace_id' => $world->workspace->id,
        'author_id' => $world->owner->id,
        'body' => 'zamanli',
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay(),
    ]);

    $world->workspace->subscription()->update(['trial_ends_at' => now()->subMinute(), 'status' => 'trialing']);
    $world->workspace->update(['trial_ends_at' => now()->subMinute()]);

    app(TrialService::class)->sync($world->workspace->fresh());

    expect($world->workspace->fresh()->status)->toBe('read_only');
    expect(Post::query()->first()->status)->toBe('paused');

    $this->actingAs($world->owner)->postJson('/api/posts', [
        'body' => 'yeni',
        'status' => 'draft',
    ])->assertStatus(423);
});

it('blocks a second seat on the starter plan', function () {
    $world = World::boot();
    $world->switchPlan('starter');
    $this->actingAs($world->owner)->postJson('/api/invites', [
        'email' => 'kisi@sinapp.test',
        'role' => 'viewer',
    ])->assertStatus(422);
});
