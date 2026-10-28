<?php

namespace Tests\Feature;

use App\Consent\GoogleConnectConsent;
use App\Contracts\Google\AnalyticsDataClient;
use App\Contracts\Google\GoogleOAuthClient;
use App\Jobs\SyncGoogleConnection;
use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\GoogleConnection;
use App\Models\GoogleSyncJob;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Google\GoogleTokenRevoker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Support\FakeGoogleOAuthClient;
use Tests\Support\RecordingAnalyticsClient;
use Tests\TestCase;

class GoogleConsentTest extends TestCase
{
    use RefreshDatabase;

    private FakeGoogleOAuthClient $oauth;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->oauth = new FakeGoogleOAuthClient;
        $this->app->instance(GoogleOAuthClient::class, $this->oauth);
    }

    public function test_oauth_start_and_callback_require_current_consent(): void
    {
        [$user, $workspace] = $this->member();
        $headers = ['X-Workspace-Slug' => $workspace->slug];

        $this->actingAs($user)->withHeaders($headers)->postJson('/api/google/oauth/start', [])
            ->assertStatus(422);
        $this->actingAs($user)->withHeaders($headers)->postJson('/api/google/oauth/start', [
            'consent' => false,
            'text_version' => $this->version(),
        ])->assertStatus(422);
        $this->actingAs($user)->withHeaders($headers)->postJson('/api/google/oauth/start', [
            'consent' => true,
            'text_version' => 'stale',
        ])->assertStatus(422)->assertJsonPath('error', 'consent_required');

        $this->assertSame(0, Consent::query()->count());
        $this->assertSame(0, $this->oauth->exchanges);
        $this->assertNotContains('ip_address', Schema::getColumnListing('consents'));

        $this->actingAs($user)->getJson('/api/google/oauth/callback?code=abc&state=missing')
            ->assertStatus(422)
            ->assertJsonPath('error', 'consent_required');
        $this->assertSame(0, $this->oauth->exchanges);
        $this->assertSame(0, GoogleConnection::query()->count());

        $started = $this->start($user, $workspace);
        Consent::query()->update(['withdrawn_at' => now()]);

        $this->actingAs($user)->getJson('/api/google/oauth/callback?'.http_build_query([
            'code' => 'abc',
            'state' => $started['state'],
        ]))->assertStatus(422)->assertJsonPath('error', 'consent_required');

        $this->assertSame(0, $this->oauth->exchanges);
        $this->assertSame(0, GoogleConnection::query()->count());
    }

    public function test_withdraw_revokes_the_google_token_and_deletes_the_local_copy(): void
    {
        [$user, $workspace] = $this->member();
        $connected = $this->connect($user, $workspace);
        $this->assertNotNull($connected->refresh_token);
        $this->assertNotSame('refresh-from-code', $connected->getRawOriginal('refresh_token'));

        Http::fake([
            GoogleTokenRevoker::ENDPOINT => Http::response('', 200),
        ]);
        Http::preventStrayRequests();

        $this->actingAs($user)
            ->withHeader('X-Workspace-Slug', $workspace->slug)
            ->deleteJson('/api/google/connection')
            ->assertOk()
            ->assertJsonPath('data.connected', false)
            ->assertJsonPath('data.sync_status', 'stopped');

        Http::assertSent(function ($request) {
            return $request->url() === GoogleTokenRevoker::ENDPOINT
                && $request['token'] === 'refresh-from-code';
        });

        $connected->refresh();
        $this->assertNull($connected->access_token);
        $this->assertNull($connected->refresh_token);
        $this->assertSame('disconnected', $connected->status);
        $this->assertSame('cancelled', GoogleSyncJob::query()->value('status'));
        $this->assertNotNull(Consent::query()->value('withdrawn_at'));

        $audit = AuditLog::query()->where('action', 'google.consent.withdrawn')->first();
        $this->assertNotNull($audit);
        $this->assertArrayNotHasKey('ip', $audit->metadata ?? []);
        $this->assertArrayNotHasKey('ip_address', $audit->metadata ?? []);
    }

    public function test_text_version_change_pauses_sync_until_reconsent(): void
    {
        [$user, $workspace] = $this->member();
        $this->connect($user, $workspace);

        $analytics = new RecordingAnalyticsClient;
        $this->app->instance(AnalyticsDataClient::class, $analytics);
        config(['consent.google.text_version' => 'google-consent-v2']);

        SyncGoogleConnection::dispatchSync($workspace->id);

        $this->assertSame(0, $analytics->calls);
        $this->assertSame('paused', GoogleConnection::query()->value('sync_status'));
        $this->assertSame('paused', GoogleSyncJob::query()->value('status'));
        $this->actingAs($user)
            ->withHeader('X-Workspace-Slug', $workspace->slug)
            ->getJson('/api/google/connection')
            ->assertOk()
            ->assertJsonPath('data.needs_reconsent', true);

        $started = $this->actingAs($user)
            ->withHeader('X-Workspace-Slug', $workspace->slug)
            ->postJson('/api/google/oauth/start', [
                'consent' => true,
                'text_version' => 'google-consent-v2',
            ])->assertOk();

        $this->actingAs($user)->getJson('/api/google/oauth/callback?'.http_build_query([
            'code' => 'reconsent',
            'state' => $started->json('data.state'),
        ]))->assertOk()->assertJsonPath('data.needs_reconsent', false);

        SyncGoogleConnection::dispatchSync($workspace->id);

        $this->assertSame(1, $analytics->calls);
        $this->assertSame('active', GoogleConnection::query()->value('sync_status'));
        $this->assertSame(0, GoogleSyncJob::query()->where('status', 'paused')->count());
    }

    public function test_published_consent_copy_matches_the_constant(): void
    {
        $root = dirname(base_path());
        $doc = (string) file_get_contents($root.'/docs/legal/acik-riza-google.md');
        $ui = (string) file_get_contents($root.'/frontend/src/lib/google-consent.ts');

        $this->assertStringContainsString(GoogleConnectConsent::TEXT, $doc);
        $this->assertStringContainsString('[Aydınlatma Metni]', $doc);
        $this->assertStringContainsString(GoogleConnectConsent::TEXT, $ui);
        $this->assertSame(
            GoogleConnectConsent::version(),
            config('consent.google.text_version'),
        );
    }

    /**
     * @return array{0: User, 1: Workspace}
     */
    private function member(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id, 'slug' => 'analitik']);
        $workspace->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_workspace_id' => $workspace->id])->save();

        return [$user, $workspace];
    }

    private function version(): string
    {
        return (string) config('consent.google.text_version');
    }

    /**
     * @return array{url: string, state: string}
     */
    private function start(User $user, Workspace $workspace): array
    {
        $response = $this->actingAs($user)
            ->withHeader('X-Workspace-Slug', $workspace->slug)
            ->postJson('/api/google/oauth/start', [
                'consent' => true,
                'text_version' => $this->version(),
            ])->assertOk();

        return $response->json('data');
    }

    private function connect(User $user, Workspace $workspace): GoogleConnection
    {
        $started = $this->start($user, $workspace);
        $this->actingAs($user)->getJson('/api/google/oauth/callback?'.http_build_query([
            'code' => 'ok-code',
            'state' => $started['state'],
        ]))->assertOk()->assertJsonPath('data.connected', true);

        $connection = GoogleConnection::query()->where('workspace_id', $workspace->id)->first();
        $this->assertNotNull($connection);

        return $connection;
    }
}
