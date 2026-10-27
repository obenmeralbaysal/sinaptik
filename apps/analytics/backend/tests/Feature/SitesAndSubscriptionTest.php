<?php

namespace Tests\Feature;

use App\Contracts\SubscriptionProvider;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitesAndSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sites_require_an_active_subscription_and_support_crud(): void
    {
        [$user, $workspace] = $this->member();

        $this->actingAs($user)
            ->withHeader('X-Workspace-Slug', $workspace->slug)
            ->getJson('/api/sites')
            ->assertStatus(402)
            ->assertJsonPath('error', 'subscription_required');

        $this->assertNull(app(SubscriptionProvider::class)->activePlan($workspace));
        $this->assertSame(0, Subscription::query()->count());

        $plan = Plan::factory()->create([
            'code' => 'analytics-starter-monthly',
            'site_limit' => 1,
            'features' => ['1 site'],
        ]);
        Subscription::factory()->create([
            'workspace_id' => $workspace->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'period_end' => now()->addMonth(),
        ]);

        $this->assertSame($plan->id, app(SubscriptionProvider::class)->activePlan($workspace)?->id);
        $this->assertTrue(app(SubscriptionProvider::class)->hasFeature($workspace, '1 site'));

        $headers = ['X-Workspace-Slug' => $workspace->slug];

        $created = $this->actingAs($user)->withHeaders($headers)->postJson('/api/sites', [
            'name' => 'Vitrin',
            'domain' => 'vitrin.example',
        ])->assertCreated();

        $id = $created->json('data.id');

        $this->actingAs($user)->withHeaders($headers)->getJson('/api/sites')
            ->assertOk()
            ->assertJsonPath('data.0.domain', 'vitrin.example');

        $this->actingAs($user)->withHeaders($headers)->patchJson("/api/sites/{$id}", [
            'name' => 'Vitrin Ana',
            'domain' => 'vitrin.example',
        ])->assertOk()->assertJsonPath('data.name', 'Vitrin Ana');

        $this->actingAs($user)->withHeaders($headers)->postJson('/api/sites', [
            'name' => 'İkinci',
            'domain' => 'ikinci.example',
        ])->assertForbidden()->assertJsonPath('error', 'limit_exceeded');

        $this->actingAs($user)->withHeaders($headers)->deleteJson("/api/sites/{$id}")->assertOk();
        $this->assertSame(0, Site::query()->count());
    }

    public function test_webhook_activates_subscription_and_rejects_a_bad_signature(): void
    {
        config(['services.sinapp.secret' => 'test-webhook-secret']);

        [$user, $workspace] = $this->member();
        $plan = Plan::factory()->create(['code' => 'analytics-pro-monthly', 'site_limit' => 10]);

        $payload = [
            'id' => 'evt_analytics_1',
            'type' => 'subscription.activated',
            'data' => [
                'subscription_id' => 'sub_analytics_1',
                'plan_code' => $plan->code,
                'workspace_slug' => $workspace->slug,
                'period_end' => now()->addYear()->toIso8601String(),
            ],
        ];

        $this->postSigned($payload)->assertOk()->assertJsonPath('status', 'accepted');
        $this->assertNotNull(app(SubscriptionProvider::class)->activePlan($workspace));

        $this->actingAs($user)
            ->withHeader('X-Workspace-Slug', $workspace->slug)
            ->postJson('/api/sites', [
                'name' => 'Magaza',
                'domain' => 'magaza.example',
            ])->assertCreated();

        $this->postJson('/api/webhooks/sinapp-subscriptions', $payload, [
            'X-Sinapp-Timestamp' => (string) time(),
            'X-Sinapp-Signature' => 'deadbeef',
            'X-Sinapp-Event' => 'subscription.activated',
        ])->assertUnauthorized();
    }

    public function test_webhook_without_a_secret_is_unavailable(): void
    {
        config(['services.sinapp.secret' => '']);

        $this->postJson('/api/webhooks/sinapp-subscriptions', ['id' => 'evt'], [
            'X-Sinapp-Event' => 'subscription.activated',
        ])->assertStatus(503);
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postSigned(array $payload)
    {
        $raw = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$raw, (string) config('services.sinapp.secret'));

        return $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SINAPP_TIMESTAMP' => $timestamp,
            'HTTP_X_SINAPP_SIGNATURE' => $signature,
            'HTTP_X_SINAPP_EVENT' => (string) $payload['type'],
        ], $raw);
    }
}
