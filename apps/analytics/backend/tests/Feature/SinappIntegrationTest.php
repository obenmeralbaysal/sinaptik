<?php

namespace Tests\Feature;

use App\Contracts\SubscriptionProvider;
use App\Models\BillingEvent;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SinappIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishes_the_sinapp_plan_catalog_from_the_plans_table(): void
    {
        $this->seed(PlanSeeder::class);
        Plan::query()->where('code', 'analytics-pro-monthly')->update(['is_active' => false]);
        Plan::query()->where('code', 'analytics-starter-monthly')->update(['features' => ['Özel satır']]);
        Plan::factory()->create([
            'code' => 'analytics-internal-monthly',
            'name' => 'İç',
            'is_public' => false,
            'is_active' => true,
            'sort_order' => 9,
        ]);

        $response = $this->getJson('/api/sinapp/plans')->assertOk();
        $plans = collect($response->json('plans'));

        $this->assertSame('sinaptik-analytics', $response->json('product'));
        $this->assertSame('TRY', $response->json('currency'));
        $this->assertSame(20, $response->json('vat_rate'));
        $this->assertIsString($response->json('updated_at'));
        $this->assertCount(2, $plans);
        $this->assertNotContains('analytics-internal-monthly', $plans->pluck('code'));
        $this->assertArrayNotHasKey('site_limit', $response->json('plans.0'));
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));

        $starter = $plans->firstWhere('code', 'analytics-starter-monthly');
        $this->assertSame('monthly', $starter['interval']);
        $this->assertSame('Başlangıç', $starter['name']);
        $this->assertEquals(298.8, (float) $starter['price_gross']);
        $this->assertEquals(249.0, (float) $starter['price_net']);
        $this->assertContains('Özel satır', $starter['features']);
        $this->assertContains('1 site', $starter['features']);
        $this->assertFalse($starter['highlighted']);
        $this->assertTrue($starter['active']);

        $pro = $plans->firstWhere('code', 'analytics-pro-monthly');
        $this->assertFalse($pro['active']);
        $this->assertTrue($pro['highlighted']);
    }

    public function test_looks_up_the_owned_workspace_and_rejects_a_bad_signature_a_stale_timestamp_and_a_reused_nonce(): void
    {
        [$user, $workspace] = $this->member('Sinapp');
        $second = Workspace::factory()->create(['owner_id' => $user->id, 'name' => 'İkinci']);
        $second->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_workspace_id' => $second->id])->save();

        $body = json_encode(['email' => strtoupper($user->email)]);
        $headers = $this->lookupHeaders($body);
        $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('account_id', $second->id)
            ->assertJsonPath('display_name', 'İkinci');

        $user->forceFill(['current_workspace_id' => $workspace->id])->save();
        $headers = $this->lookupHeaders($body);
        $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk()
            ->assertJsonPath('account_id', $workspace->id)
            ->assertJsonPath('display_name', 'Sinapp');

        $missing = json_encode(['email' => 'yok@sinaptik.test']);
        $headers = $this->lookupHeaders($missing);
        $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $missing)
            ->assertOk()
            ->assertExactJson(['exists' => false, 'account_id' => null, 'display_name' => null]);

        $headers = $this->lookupHeaders($body, ['secret' => 'wrong-secret']);
        $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertUnauthorized();

        $headers = $this->lookupHeaders($body, ['timestamp' => (string) (time() - 3600)]);
        $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertUnauthorized();

        $headers = $this->lookupHeaders($body, ['nonce' => 'tekrar-nonce']);
        $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk();
        $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertUnauthorized();
    }

    public function test_rate_limits_signed_account_lookups(): void
    {
        config(['billing.sinapp.lookup_rate_limit' => 2]);
        $body = json_encode(['email' => 'yok@sinaptik.test']);

        foreach ([1, 2] as $attempt) {
            $headers = $this->lookupHeaders($body, ['nonce' => 'rate-'.$attempt]);
            $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
                ->assertOk();
        }

        $headers = $this->lookupHeaders($body, ['nonce' => 'rate-3']);
        $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertStatus(429);
    }

    public function test_activates_renews_cancels_and_expires_a_subscription_idempotently(): void
    {
        [$user, $workspace] = $this->member();
        $pro = Plan::factory()->create([
            'code' => 'analytics-pro-monthly',
            'name' => 'Pro',
            'interval' => 'monthly',
            'price_gross_cents' => 89880,
            'site_limit' => 10,
        ]);
        Plan::factory()->create([
            'code' => 'analytics-starter-monthly',
            'name' => 'Başlangıç',
            'interval' => 'monthly',
            'price_gross_cents' => 29880,
            'site_limit' => 1,
        ]);

        $payload = $this->event('evt_act', 'subscription.activated', $workspace->id, [
            'plan_code' => $pro->code,
            'amount_gross' => 898.8,
        ]);
        [$body, $headers] = $this->webhook($payload);
        $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk()
            ->assertJsonPath('duplicate', false);
        $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $subscription = Subscription::query()->where('workspace_id', $workspace->id)->latest('id')->first();
        $this->assertSame('active', $subscription->status);
        $this->assertSame('analytics', $subscription->product_code);
        $this->assertSame($pro->code, $subscription->plan->code);
        $this->assertSame('monthly', $subscription->period);
        $this->assertSame('sinapp', $subscription->gateway);
        $this->assertSame('sub_evt_act', $subscription->provider_subscription_id);
        $this->assertSame(1, Payment::query()->where('event_id', 'evt_act')->count());
        $this->assertSame(89880, Payment::query()->where('event_id', 'evt_act')->value('amount_cents'));
        $this->assertSame(1, BillingEvent::query()->where('event_id', 'evt_act')->count());
        $this->assertSame($pro->id, app(SubscriptionProvider::class)->activePlan($workspace)?->id);

        $renewEnd = now()->addMonths(2)->toIso8601String();
        [$body, $headers] = $this->webhook($this->event('evt_renew', 'subscription.renewed', $workspace->id, [
            'period_end' => $renewEnd,
            'plan_code' => $pro->code,
            'amount_gross' => 898.8,
        ]));
        $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk()
            ->assertJsonPath('duplicate', false);

        $subscription = $subscription->fresh();
        $this->assertTrue($subscription->period_end->equalTo(Carbon::parse($renewEnd)));
        $this->assertSame(89880, Payment::query()->where('event_id', 'evt_renew')->value('amount_cents'));

        [$body, $headers] = $this->webhook($this->event('evt_cancel', 'subscription.canceled', $workspace->id, [
            'period_end' => now()->addMonth()->toIso8601String(),
        ]));
        $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk();
        $subscription = $subscription->fresh();
        $this->assertTrue($subscription->cancel_at_period_end);
        $this->assertSame('active', $subscription->status);
        $this->assertNotNull(app(SubscriptionProvider::class)->activePlan($workspace));

        [$body, $headers] = $this->webhook($this->event('evt_expire', 'subscription.expired', $workspace->id));
        $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk();

        $this->assertSame('read_only', $subscription->fresh()->status);
        $this->assertNull(app(SubscriptionProvider::class)->activePlan($workspace));
        $this->assertNotSame('analytics-starter-monthly', Subscription::query()->first()->plan->code);

        unset($user);
    }

    public function test_rejects_a_bad_sinapp_webhook_signature_an_unknown_plan_and_a_missing_account(): void
    {
        [, $workspace] = $this->member();
        Plan::factory()->create(['code' => 'analytics-pro-monthly', 'interval' => 'monthly']);
        $payload = $this->event('evt_bad', 'subscription.activated', $workspace->id);

        [$body, $headers] = $this->webhook($payload, secret: 'wrong-secret');
        $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertUnauthorized();

        [$body, $headers] = $this->webhook($payload, time() - 3600);
        $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertUnauthorized();

        [$body, $headers] = $this->webhook($this->event('evt_plan', 'subscription.activated', $workspace->id, [
            'plan_code' => 'analytics-yok-monthly',
        ]));
        $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertStatus(422);
        $this->assertFalse(BillingEvent::query()->where('event_id', 'evt_plan')->exists());
        $this->assertSame(0, Subscription::query()->count());

        [$body, $headers] = $this->webhook($this->event('evt_missing', 'subscription.activated', 999999));
        $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertNotFound();
    }

    public function test_sends_checkout_to_the_sinapp_purchase_page_with_the_email_prefilled(): void
    {
        [$user, $workspace] = $this->member();
        $plan = Plan::factory()->create([
            'code' => 'analytics-pro-monthly',
            'interval' => 'monthly',
            'is_active' => true,
        ]);
        config([
            'billing.gateway' => 'sinapp',
            'billing.sinapp.base_url' => 'https://sinapp.com.tr',
            'billing.sinapp.purchase_path' => '/satin-al/sinaptik-analytics',
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Workspace-Slug', $workspace->slug)
            ->postJson('/api/billing/checkout', [
                'plan_code' => $plan->code,
                'period' => 'monthly',
            ])->assertOk();

        $url = $response->json('url');
        $this->assertSame($url, $response->json('redirect_url'));
        $this->assertStringStartsWith('https://sinapp.com.tr/satin-al/sinaptik-analytics?', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame($plan->code, $query['plan']);
        $this->assertSame($user->email, $query['email']);
        $this->assertSame((string) $workspace->id, $query['account_id']);
        $this->assertStringContainsString('/pricing?status=ok', $query['return_url']);

        config(['billing.gateway' => 'fake']);
        $fake = $this->actingAs($user)
            ->withHeader('X-Workspace-Slug', $workspace->slug)
            ->postJson('/api/billing/checkout', [
                'plan_code' => $plan->code,
                'period' => 'monthly',
            ])->assertOk();
        $this->assertStringContainsString('/pricing/sahte?session=', $fake->json('url'));
        $this->assertSame($fake->json('url'), $fake->json('redirect_url'));
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function lookupHeaders(string $body, array $overrides = []): array
    {
        $timestamp = (string) ($overrides['timestamp'] ?? time());
        $nonce = (string) ($overrides['nonce'] ?? ('nonce-'.bin2hex(random_bytes(8))));
        $path = '/api/sinapp/accounts/lookup';
        $canonical = $timestamp."\n".$nonce."\nPOST\n".$path."\n".hash('sha256', $body);
        $signature = $overrides['signature'] ?? hash_hmac('sha256', $canonical, $overrides['secret'] ?? (string) config('billing.sinapp.secret'));

        return [
            'X-Sinapp-Key' => $overrides['key'] ?? (string) config('billing.sinapp.integration_key'),
            'X-Sinapp-Timestamp' => $timestamp,
            'X-Sinapp-Nonce' => $nonce,
            'X-Sinapp-Signature' => $signature,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: array<string, string>}
     */
    private function webhook(array $payload, ?int $timestamp = null, ?string $secret = null): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp ??= time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret ?? (string) config('billing.sinapp.secret'));

        return [$body, [
            'X-Sinapp-Timestamp' => (string) $timestamp,
            'X-Sinapp-Signature' => $signature,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function event(string $id, string $type, int $accountId, array $data = []): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'created_at' => now()->toIso8601String(),
            'data' => array_merge([
                'subscription_id' => 'sub_'.$id,
                'account_id' => $accountId,
                'plan_code' => 'analytics-pro-monthly',
                'period_start' => now()->toIso8601String(),
                'period_end' => now()->addMonth()->toIso8601String(),
                'amount_gross' => 898.8,
                'currency' => 'TRY',
            ], $data),
        ];
    }

    /**
     * @return array{0: User, 1: Workspace}
     */
    private function member(string $name = 'Analitik'): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id, 'name' => $name]);
        $workspace->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_workspace_id' => $workspace->id])->save();

        return [$user, $workspace];
    }
}
