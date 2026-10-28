<?php

use App\Jobs\SyncInsights;
use App\Models\AccountInsight;
use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\SocialAccount;
use App\Services\Meta\FakeMetaDriver;
use App\Support\WorkspaceContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\Support\World;

beforeEach(function () {
    FakeMetaDriver::$revokedTokens = [];
});

it('rejects meta oauth start and callback without consent', function () {
    $world = World::boot();
    $text = trim((string) file_get_contents(resource_path('consent/meta.txt')));

    $this->actingAs($world->owner)
        ->getJson('/api/meta/consent')
        ->assertOk()
        ->assertJsonPath('provider', 'meta')
        ->assertJsonPath('text', $text)
        ->assertJsonPath('text_version', 'v1')
        ->assertJsonPath('notice_url', '/aydinlatma-metni')
        ->assertJsonPath('granted', false);

    $this->actingAs($world->owner)
        ->postJson('/api/meta/connect', [])
        ->assertStatus(422);

    $this->actingAs($world->owner)
        ->postJson('/api/meta/connect', ['consent' => false, 'text_version' => 'v1'])
        ->assertStatus(422);

    expect(Consent::query()->count())->toBe(0);

    Cache::put('meta.connect.'.hash('sha256', 'bare'), [
        'workspace_id' => $world->workspace->id,
        'user_id' => $world->owner->id,
    ], now()->addMinutes(10));

    $this->get('/api/meta/callback?code=abc&state=bare')->assertStatus(422);

    app(WorkspaceContext::class)->activate(null, true);
    expect(SocialAccount::query()->withoutGlobalScopes()->count())->toBe(0);
    expect(Schema::hasColumn('consents', 'ip'))->toBeFalse();
});

it('withdraws meta consent by revoking permissions and deleting local tokens', function () {
    $world = World::boot();

    $started = $this->actingAs($world->owner)
        ->postJson('/api/meta/connect', ['consent' => true, 'text_version' => 'v1'])
        ->assertOk();

    $state = metaConnectState($started->json('url'));
    $this->get('/api/meta/callback?code=abc&state='.$state)
        ->assertRedirect();

    $userAccount = SocialAccount::query()->where('platform', 'meta_user')->firstOrFail();
    $token = (string) $userAccount->access_token;
    expect($token)->not->toBe('');

    $page = SocialAccount::query()->create([
        'workspace_id' => $world->workspace->id,
        'platform' => 'facebook_page',
        'external_id' => '111',
        'name' => 'Sayfa',
        'access_token' => 'page-token-111',
        'status' => 'connected',
        'parent_external_id' => $userAccount->external_id,
    ]);

    $this->actingAs($world->owner)->deleteJson('/api/meta/connection')->assertOk();

    expect(FakeMetaDriver::$revokedTokens)->toContain($token);
    expect($userAccount->fresh()->access_token)->toBeNull();
    expect($userAccount->fresh()->status)->toBe('disconnected');
    expect($page->fresh()->access_token)->toBeNull();
    expect($page->fresh()->status)->toBe('disconnected');
    expect(Consent::query()->firstOrFail()->withdrawn_at)->not->toBeNull();

    $log = AuditLog::query()->where('action', 'meta.consent.withdrawn')->first();
    expect($log)->not->toBeNull();
    expect($log->ip)->toBeNull();

    $this->actingAs($world->owner)
        ->postJson('/api/accounts/sync')
        ->assertOk()
        ->assertJsonPath('paused', true);
});

it('pauses meta sync until the current consent text is accepted again', function () {
    $world = World::boot();
    app(WorkspaceContext::class)->activate($world->workspace->id);

    $page = SocialAccount::query()->create([
        'workspace_id' => $world->workspace->id,
        'platform' => 'facebook_page',
        'external_id' => '111',
        'name' => 'Sayfa',
        'access_token' => 'page-token-111',
        'status' => 'connected',
    ]);
    Consent::query()->create([
        'user_id' => $world->owner->id,
        'workspace_id' => $world->workspace->id,
        'provider' => 'meta',
        'text_version' => 'v1',
        'granted_at' => now(),
    ]);

    $this->actingAs($world->owner)->postJson('/api/accounts/sync')->assertOk()->assertJsonPath('paused', false);
    expect(AccountInsight::query()->count())->toBe(1);

    config(['consent.meta.text_version' => 'v2']);
    AccountInsight::query()->delete();

    $this->actingAs($world->owner)
        ->postJson('/api/meta/connect', ['consent' => true, 'text_version' => 'v1'])
        ->assertStatus(422);

    $this->actingAs($world->owner)
        ->postJson('/api/accounts/sync')
        ->assertOk()
        ->assertJsonPath('paused', true);

    SyncInsights::dispatch($page->id, $world->workspace->id);
    expect(AccountInsight::query()->count())->toBe(0);

    $this->actingAs($world->owner)
        ->postJson('/api/meta/connect', ['consent' => true, 'text_version' => 'v2'])
        ->assertOk();

    $this->actingAs($world->owner)->postJson('/api/accounts/sync')->assertOk()->assertJsonPath('paused', false);
    expect(AccountInsight::query()->count())->toBe(1);
});

function metaConnectState(string $url): string
{
    $query = [];
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return (string) ($query['state'] ?? '');
}
