<?php

use App\Models\Post;
use App\Support\WorkspaceContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\World;

it('hides other tenants even when the eloquent scope is removed', function () {
    $first = World::boot();
    $secondOwner = \App\Models\User::factory()->create();
    $second = app(\App\Services\WorkspaceService::class)->create($secondOwner, 'Diger');

    app(WorkspaceContext::class)->activate($first->workspace->id);
    Post::query()->create([
        'workspace_id' => $first->workspace->id,
        'author_id' => $first->owner->id,
        'body' => 'birinci',
        'status' => 'draft',
    ]);

    app(WorkspaceContext::class)->activate($second->id);
    Post::query()->create([
        'workspace_id' => $second->id,
        'author_id' => $secondOwner->id,
        'body' => 'ikinci',
        'status' => 'draft',
    ]);

    app(WorkspaceContext::class)->activate($first->workspace->id);
    $rows = DB::table('posts')->get();
    expect($rows)->toHaveCount(1);
    expect($rows->first()->body)->toBe('birinci');

    expect(function () use ($second) {
        DB::transaction(function () use ($second) {
            DB::table('posts')->insert([
                'workspace_id' => $second->id,
                'body' => 'sizma',
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    })->toThrow(QueryException::class);

    $this->actingAs($first->owner)
        ->withHeader('X-Workspace-Id', (string) $second->id)
        ->getJson('/api/posts')
        ->assertForbidden();
});
