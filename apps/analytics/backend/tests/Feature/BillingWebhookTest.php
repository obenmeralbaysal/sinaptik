<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_a_bad_billing_signature_and_an_old_timestamp(): void
    {
        [$user, $workspace] = $this->member();
        $plan = Plan::factory()->create(['code' => 'analytics-pro-monthly', 'interval' => 'monthly']);
        $payload = [
            'event_id' => 'evt_bad',
            'type' => 'subscription.activated',
            'data' => [
                'external_reference' => (string) $workspace->id,
                'plan_code' => $plan->code,
                'period' => 'monthly',
            ],
        ];

        [$body, $headers] = $this->signed($payload, 'wrong-secret');
        $this->call('POST', '/api/webhooks/billing/sinapp', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertUnauthorized();

        [$body, $headers] = $this->signed($payload, 'test-secret', time() - 3600);
        $this->call('POST', '/api/webhooks/billing/sinapp', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertUnauthorized();

        unset($user);
    }

    public function test_activates_a_subscription_once_for_a_repeated_event_id(): void
    {
        [$user, $workspace] = $this->member();
        $plan = Plan::factory()->create([
            'code' => 'analytics-pro-yearly',
            'interval' => 'yearly',
            'price_gross_cents' => 898800,
        ]);
        $payload = [
            'event_id' => 'evt_1',
            'type' => 'subscription.activated',
            'data' => [
                'external_reference' => (string) $workspace->id,
                'plan_code' => $plan->code,
                'period' => 'yearly',
                'amount_minor' => 898800,
                'gateway_subscription_id' => 'sub_1',
                'current_period_end' => now()->addYear()->toIso8601String(),
            ],
        ];

        [$body, $headers] = $this->signed($payload);
        $this->call('POST', '/api/webhooks/billing/sinapp', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk()
            ->assertJsonPath('duplicate', false);
        $this->call('POST', '/api/webhooks/billing/sinapp', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $subscription = Subscription::query()->where('workspace_id', $workspace->id)->latest('id')->first();
        $this->assertNotNull($subscription);
        $this->assertSame('active', $subscription->status);
        $this->assertSame('analytics', $subscription->product_code);
        $this->assertSame($plan->code, $subscription->plan->code);
        $this->assertSame('yearly', $subscription->period);
        $this->assertSame(1, Payment::query()->where('event_id', 'evt_1')->count());
        $this->assertSame(898800, Payment::query()->where('event_id', 'evt_1')->value('amount_cents'));
        $this->assertSame($plan->id, app(\App\Contracts\SubscriptionProvider::class)->activePlan($workspace)?->id);
        $this->assertNotSame('starter', app(\App\Contracts\SubscriptionProvider::class)->activePlan($workspace)?->code);

        unset($user);
    }

    public function test_unknown_plan_does_not_fall_back_to_starter(): void
    {
        [, $workspace] = $this->member();
        Plan::factory()->create(['code' => 'analytics-starter-monthly']);
        $payload = [
            'event_id' => 'evt_missing_plan',
            'type' => 'subscription.activated',
            'data' => [
                'external_reference' => (string) $workspace->id,
                'plan_code' => 'analytics-yok-monthly',
                'period' => 'monthly',
            ],
        ];

        [$body, $headers] = $this->signed($payload);
        $this->call('POST', '/api/webhooks/billing/sinapp', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertStatus(422);

        $this->assertSame(0, Subscription::query()->count());
        $this->assertNull(app(\App\Contracts\SubscriptionProvider::class)->activePlan($workspace));
    }

    public function test_webhook_without_a_secret_is_unavailable(): void
    {
        config([
            'billing.hub.secret' => '',
            'billing.hub.integration_key' => '',
        ]);

        $this->postJson('/api/webhooks/billing/sinapp', ['event_id' => 'evt'])->assertStatus(503);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: array<string, string>}
     */
    private function signed(array $payload, ?string $secret = null, ?int $timestamp = null): array
    {
        $body = json_encode($payload);
        $timestamp ??= time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret ?? (string) config('billing.hub.secret'));

        return [$body, [
            'X-Integration-Key' => (string) config('billing.hub.integration_key'),
            'X-Timestamp' => (string) $timestamp,
            'X-Signature' => $signature,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]];
    }

    /**
     * @return array{0: User, 1: Workspace}
     */
    private function member(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_workspace_id' => $workspace->id])->save();

        return [$user, $workspace];
    }
}
