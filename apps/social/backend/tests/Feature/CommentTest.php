<?php

use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\User;
use App\Models\WorkspaceUser;
use App\Services\ConsentService;
use App\Services\Meta\FakeMetaDriver;
use App\Services\Meta\GraphMetaDriver;
use App\Services\Meta\MetaDriver;
use App\Services\WorkspaceService;
use App\Support\Role;
use App\Support\WorkspaceContext;
use Illuminate\Support\Facades\Http;
use Tests\Support\World;

beforeEach(function () {
    FakeMetaDriver::reset();
});

it('lists replies hides and deletes comments for a published post', function () {
    [$world, $post] = commentWorld();

    $listed = $this->actingAs($world->owner)
        ->getJson('/api/posts/'.$post->id.'/comments')
        ->assertOk()
        ->json('comments');

    expect($listed)->toHaveCount(1);
    expect($listed[0]['author_username'])->toBe('ayse');
    expect($listed[0]['platform'])->toBe('facebook');
    expect($listed[0]['body'])->toBe('Harika paylaşım');

    $this->actingAs($world->owner)
        ->postJson('/api/comments/'.$listed[0]['id'].'/reply', ['message' => 'Teşekkürler'])
        ->assertCreated()
        ->assertJsonPath('comment.body', 'Teşekkürler')
        ->assertJsonPath('comment.parent_external_id', $listed[0]['external_id']);

    $again = $this->actingAs($world->owner)
        ->getJson('/api/posts/'.$post->id.'/comments')
        ->assertOk()
        ->json('comments');
    expect(collect($again)->pluck('body'))->toContain('Teşekkürler');

    $reply = collect($again)->firstWhere('body', 'Teşekkürler');
    $this->actingAs($world->owner)
        ->postJson('/api/comments/'.$reply['id'].'/hide', ['hidden' => true])
        ->assertOk()
        ->assertJsonPath('comment.hidden_at', fn ($value) => $value !== null);

    $this->actingAs($world->owner)
        ->deleteJson('/api/comments/'.$reply['id'])
        ->assertOk();

    expect(SocialComment::withTrashed()->find($reply['id'])?->deleted_at)->not->toBeNull();
    $afterDelete = $this->actingAs($world->owner)
        ->getJson('/api/posts/'.$post->id.'/comments')
        ->assertOk()
        ->json('comments');
    expect(collect($afterDelete)->pluck('body'))->not->toContain('Teşekkürler');
});

it('syncs an instagram comment and the workspace inbox', function () {
    [$world, $pagePost] = commentWorld();
    app(WorkspaceContext::class)->activate($world->workspace->id);
    $ig = SocialAccount::query()->create([
        'workspace_id' => $world->workspace->id,
        'platform' => 'instagram',
        'external_id' => '222',
        'name' => 'Sinapp IG',
        'username' => 'sinapp',
        'access_token' => 'ig-token',
        'status' => 'connected',
    ]);
    $media = Post::query()->create([
        'workspace_id' => $world->workspace->id,
        'author_id' => $world->owner->id,
        'social_account_id' => $ig->id,
        'status' => 'published',
        'external_id' => 'ig_media_1',
        'published_at' => now(),
        'body' => 'IG',
    ]);

    $this->actingAs($world->owner)
        ->getJson('/api/posts/'.$media->id.'/comments')
        ->assertOk()
        ->assertJsonPath('comments.0.platform', 'instagram')
        ->assertJsonPath('comments.0.author_username', 'ayse');

    $this->actingAs($world->owner)
        ->postJson('/api/comments/sync')
        ->assertOk()
        ->assertJsonPath('synced_posts', 2);

    $inbox = $this->actingAs($world->owner)->getJson('/api/comments')->assertOk()->json('comments');
    expect(collect($inbox)->pluck('post_id'))->toContain($pagePost->id, $media->id);
});

it('isolates comments by workspace and blocks viewers from replying', function () {
    [$world, $post] = commentWorld();
    $commentId = $this->actingAs($world->owner)
        ->getJson('/api/posts/'.$post->id.'/comments')
        ->json('comments.0.id');

    $viewer = User::factory()->create([
        'email_verified_at' => now(),
        'current_workspace_id' => $world->workspace->id,
    ]);
    WorkspaceUser::query()->create([
        'workspace_id' => $world->workspace->id,
        'user_id' => $viewer->id,
        'role' => Role::VIEWER,
        'ads_write' => false,
    ]);
    $this->actingAs($viewer)
        ->getJson('/api/posts/'.$post->id.'/comments')
        ->assertOk();
    $this->actingAs($viewer)
        ->postJson('/api/comments/'.$commentId.'/reply', ['message' => 'hayır'])
        ->assertForbidden();

    $otherOwner = User::factory()->create(['email_verified_at' => now()]);
    $other = app(WorkspaceService::class)->create($otherOwner, 'Diger');
    $this->actingAs($otherOwner)
        ->withHeader('X-Workspace-Id', (string) $other->id)
        ->getJson('/api/posts/'.$post->id.'/comments')
        ->assertNotFound();
    $this->actingAs($otherOwner)
        ->withHeader('X-Workspace-Id', (string) $other->id)
        ->postJson('/api/comments/'.$commentId.'/reply', ['message' => 'sizma'])
        ->assertNotFound();
    $this->actingAs($otherOwner)
        ->withHeader('X-Workspace-Id', (string) $other->id)
        ->getJson('/api/comments')
        ->assertOk()
        ->assertJsonCount(0, 'comments');
});

it('rejects comment sync for drafts and without meta consent', function () {
    $world = World::boot();
    app(WorkspaceContext::class)->activate($world->workspace->id);
    $account = SocialAccount::query()->create([
        'workspace_id' => $world->workspace->id,
        'platform' => 'facebook_page',
        'external_id' => '111',
        'name' => 'Sayfa',
        'access_token' => 'page-token',
        'status' => 'connected',
    ]);
    $draft = Post::query()->create([
        'workspace_id' => $world->workspace->id,
        'author_id' => $world->owner->id,
        'social_account_id' => $account->id,
        'status' => 'draft',
        'body' => 'taslak',
    ]);

    $this->actingAs($world->owner)
        ->getJson('/api/posts/'.$draft->id.'/comments')
        ->assertStatus(422);

    $published = Post::query()->create([
        'workspace_id' => $world->workspace->id,
        'author_id' => $world->owner->id,
        'social_account_id' => $account->id,
        'status' => 'published',
        'external_id' => 'ext_no_consent',
        'published_at' => now(),
        'body' => 'yayin',
    ]);
    $this->actingAs($world->owner)
        ->getJson('/api/posts/'.$published->id.'/comments')
        ->assertStatus(422);
});

it('purges comment body and author username after the retention window', function () {
    [$world] = commentWorld();
    app(WorkspaceContext::class)->activate($world->workspace->id);
    $account = SocialAccount::query()->where('platform', 'facebook_page')->firstOrFail();
    $old = SocialComment::query()->create([
        'workspace_id' => $world->workspace->id,
        'social_account_id' => $account->id,
        'platform' => 'facebook',
        'external_id' => 'old_cmt',
        'author_external_id' => 'user_old',
        'author_username' => 'eski',
        'body' => 'eski yorum',
        'raw' => ['id' => 'old_cmt'],
        'posted_at' => now()->subDays(100),
    ]);

    $this->artisan('sinsocial:purge-comments')->assertOk();

    $old->refresh();
    expect($old->body)->toBeNull();
    expect($old->author_username)->toBeNull();
    expect($old->author_external_id)->toBeNull();
    expect($old->raw)->toBeNull();
    expect($old->purged_at)->not->toBeNull();
    expect($old->external_id)->toBe('old_cmt');
});

it('leaves meta webhooks unimplemented and advertises comment scopes on the fake driver', function () {
    $this->postJson('/api/meta/webhooks', ['entry' => [['id' => '1']]])
        ->assertOk()
        ->assertJsonPath('status', 'ignored')
        ->assertJsonPath('phase', 2);

    $scopes = app(MetaDriver::class)->debugToken('page-token')['scopes'];
    expect($scopes)->toContain('pages_read_user_content', 'pages_manage_engagement', 'instagram_manage_comments');
});

it('calls the page and instagram comment endpoints', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'data' => [[
                'id' => 'c1',
                'message' => 'hi',
                'from' => ['id' => '9', 'name' => 'Ali'],
                'created_time' => '2026-01-02T00:00:00+0000',
            ]],
            'id' => 'reply_1',
        ]),
    ]);

    $page = new SocialAccount([
        'platform' => 'facebook_page',
        'external_id' => '111',
        'access_token' => 'page-token',
    ]);
    $ig = new SocialAccount([
        'platform' => 'instagram',
        'external_id' => '222',
        'access_token' => 'ig-token',
    ]);
    $graph = app(GraphMetaDriver::class);

    $rows = $graph->listComments($page, 'post_1');
    expect($rows[0]['author_username'])->toBe('Ali');
    expect($rows[0]['body'])->toBe('hi');

    $graph->replyToComment($page, 'c1', 'selam');
    $graph->hideComment($page, 'c1', true);
    $graph->deleteComment($page, 'c1');
    $graph->replyToComment($ig, 'ig_c1', 'selam');
    $graph->hideComment($ig, 'ig_c1', true);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/post_1/comments') && str_contains($request->url(), 'filter=stream'));
    Http::assertSent(fn ($request) => $request->method() === 'POST' && str_contains($request->url(), '/c1/comments'));
    Http::assertSent(fn ($request) => $request->method() === 'POST' && str_contains($request->url(), '/ig_c1/replies'));
    Http::assertSent(fn ($request) => $request->method() === 'POST' && str_contains($request->url(), '/ig_c1') && str_contains($request->body(), 'hide'));
    Http::assertSent(fn ($request) => $request->method() === 'DELETE' && str_contains($request->url(), '/c1'));
});

function commentWorld(): array
{
    $world = World::boot();
    app(WorkspaceContext::class)->activate($world->workspace->id);
    app(ConsentService::class)->grant($world->workspace, $world->owner, 'meta', 'v1');
    $account = SocialAccount::query()->create([
        'workspace_id' => $world->workspace->id,
        'platform' => 'facebook_page',
        'external_id' => '111',
        'name' => 'Sinapp Sayfa',
        'username' => 'sinapp',
        'access_token' => 'page-token-111',
        'status' => 'connected',
    ]);
    $post = Post::query()->create([
        'workspace_id' => $world->workspace->id,
        'author_id' => $world->owner->id,
        'social_account_id' => $account->id,
        'status' => 'published',
        'external_id' => 'ext_post_1',
        'published_at' => now(),
        'body' => 'Gönderi',
    ]);

    return [$world, $post];
}
