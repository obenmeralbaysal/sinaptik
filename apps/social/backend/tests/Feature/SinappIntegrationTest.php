<?php

use App\Models\BillingEvent;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Subscription;
use App\Services\WorkspaceService;
use App\Support\WorkspaceContext;
use Tests\Support\World;

function sinappLookupHeaders(string $body, array $overrides = []): array
{
    $timestamp = (string) ($overrides['timestamp'] ?? time());
    $nonce = (string) ($overrides['nonce'] ?? ('nonce-'.bin2hex(random_bytes(8))));
    $path = '/api/sinapp/accounts/lookup';
    $canonical = $timestamp."\n".$nonce."\nPOST\n".$path."\n".hash('sha256', $body);
    $signature = $overrides['signature'] ?? hash_hmac('sha256', $canonical, $overrides['secret'] ?? 'test-secret');

    return [
        'X-Sinapp-Key' => $overrides['key'] ?? 'test-key',
        'X-Sinapp-Timestamp' => $timestamp,
        'X-Sinapp-Nonce' => $nonce,
        'X-Sinapp-Signature' => $signature,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];
}

function sinappWebhookCall(array $payload, ?int $timestamp = null, ?string $secret = null): array
{
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $timestamp ??= time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret ?? 'test-secret');

    return [$body, [
        'X-Sinapp-Timestamp' => (string) $timestamp,
        'X-Sinapp-Signature' => $signature,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ]];
}

function sinappEvent(string $id, string $type, int $accountId, array $data = []): array
{
    return [
        'id' => $id,
        'type' => $type,
        'created_at' => now()->toIso8601String(),
        'data' => array_merge([
            'subscription_id' => 'sub_'.$id,
            'product_account_email' => 'owner@sinaptik.test',
            'account_id' => $accountId,
            'plan_code' => 'sinaptik-social-agency-yearly',
            'period_start' => now()->toIso8601String(),
            'period_end' => now()->addYear()->toIso8601String(),
            'amount_gross' => 39990,
            'amount_net' => 33325,
            'vat_amount' => 6665,
            'currency' => 'TRY',
            'billing' => ['source' => 'sinapp'],
        ], $data),
    ];
}

it('publishes the sinapp plan catalog from the plans table', function () {
    World::boot();
    Plan::query()->where('code', 'agency')->update(['is_active' => false]);
    Plan::query()->where('code', 'starter')->update(['features' => ['Özel satır']]);
    Plan::query()->create([
        'code' => 'internal',
        'name' => 'İç',
        'price_monthly_minor' => 100,
        'price_yearly_minor' => 1000,
        'currency' => 'TRY',
        'limits' => [],
        'features' => [],
        'is_public' => false,
        'is_active' => true,
        'sort_order' => 9,
    ]);

    $response = $this->getJson('/api/sinapp/plans')->assertOk();
    $plans = collect($response->json('plans'));

    expect($response->json('product'))->toBe('sinaptik-social');
    expect($response->json('currency'))->toBe('TRY');
    expect($response->json('vat_rate'))->toBe(20);
    expect($response->json('updated_at'))->toBeString();
    expect($plans)->toHaveCount(6);
    expect($plans->pluck('code'))->not->toContain('sinaptik-social-internal-monthly');

    $starter = $plans->firstWhere('code', 'sinaptik-social-starter-monthly');
    expect($starter['interval'])->toBe('monthly');
    expect($starter['name'])->toBe('Başlangıç');
    expect((float) $starter['price_gross'])->toEqual(499.0);
    expect((float) $starter['price_net'])->toEqual(415.83);
    expect($starter['features'])->toContain('Özel satır');
    expect($starter['features'])->toContain('3 sosyal hesap');
    expect($starter['highlighted'])->toBeFalse();
    expect($starter['active'])->toBeTrue();

    $proYear = $plans->firstWhere('code', 'sinaptik-social-pro-yearly');
    expect((float) $proYear['price_gross'])->toEqual(14990.0);
    expect((float) $proYear['price_net'])->toEqual(12491.67);
    expect($proYear['highlighted'])->toBeTrue();
    expect($proYear['interval'])->toBe('yearly');

    $agency = $plans->firstWhere('code', 'sinaptik-social-agency-monthly');
    expect($agency['active'])->toBeFalse();
    expect($plans->where('highlighted', true)->pluck('code')->all())->toBe([
        'sinaptik-social-pro-monthly',
        'sinaptik-social-pro-yearly',
    ]);
});

it('looks up the owned workspace and rejects a bad signature, a stale timestamp, and a reused nonce', function () {
    $world = World::boot();
    $second = app(WorkspaceService::class)->create($world->owner, 'İkinci');
    $body = json_encode(['email' => strtoupper($world->owner->email)]);

    [$headers] = [sinappLookupHeaders($body)];
    $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertOk()
        ->assertJsonPath('exists', true)
        ->assertJsonPath('account_id', $second->id)
        ->assertJsonPath('display_name', 'İkinci');

    $world->owner->forceFill(['current_workspace_id' => $world->workspace->id])->save();
    $headers = sinappLookupHeaders($body);
    $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertOk()
        ->assertJsonPath('account_id', $world->workspace->id)
        ->assertJsonPath('display_name', 'Sinapp');

    $missing = json_encode(['email' => 'yok@sinaptik.test']);
    $headers = sinappLookupHeaders($missing);
    $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $missing)
        ->assertOk()
        ->assertExactJson(['exists' => false, 'account_id' => null, 'display_name' => null]);

    $headers = sinappLookupHeaders($body, ['secret' => 'wrong-secret']);
    $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertUnauthorized();

    $headers = sinappLookupHeaders($body, ['timestamp' => time() - 3600]);
    $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertUnauthorized();

    $headers = sinappLookupHeaders($body, ['nonce' => 'tekrar-nonce']);
    $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertOk();
    $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertUnauthorized();
});

it('rate limits signed account lookups', function () {
    config(['billing.sinapp.lookup_rate_limit' => 2]);
    $body = json_encode(['email' => 'yok@sinaptik.test']);

    foreach ([1, 2] as $attempt) {
        $headers = sinappLookupHeaders($body, ['nonce' => 'rate-'.$attempt]);
        $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk();
    }

    $headers = sinappLookupHeaders($body, ['nonce' => 'rate-3']);
    $this->call('POST', '/api/sinapp/accounts/lookup', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertStatus(429);
});

it('activates, renews, cancels, and expires a subscription idempotently', function () {
    $world = World::boot();
    app(WorkspaceContext::class)->activate($world->workspace->id);
    Post::query()->create([
        'workspace_id' => $world->workspace->id,
        'author_id' => $world->owner->id,
        'body' => 'zamanli',
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay(),
    ]);

    $payload = sinappEvent('evt_act', 'subscription.activated', $world->workspace->id);
    [$body, $headers] = sinappWebhookCall($payload);
    $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertOk()
        ->assertJsonPath('duplicate', false);
    $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertOk()
        ->assertJsonPath('duplicate', true);

    $subscription = Subscription::query()->where('workspace_id', $world->workspace->id)->latest('id')->first();
    expect($subscription->status)->toBe('active');
    expect($subscription->plan->code)->toBe('agency');
    expect($subscription->period)->toBe('yearly');
    expect($subscription->gateway)->toBe('sinapp');
    expect($subscription->gateway_subscription_id)->toBe('sub_evt_act');
    expect($world->workspace->fresh()->status)->toBe('active');
    expect(Payment::query()->where('event_id', 'evt_act')->count())->toBe(1);
    expect(Payment::query()->where('event_id', 'evt_act')->first()->amount_minor)->toBe(3999000);
    expect(BillingEvent::query()->where('event_id', 'evt_act')->count())->toBe(1);

    $renewEnd = now()->addYears(2)->toIso8601String();
    [$body, $headers] = sinappWebhookCall(sinappEvent('evt_renew', 'subscription.renewed', $world->workspace->id, [
        'period_end' => $renewEnd,
        'plan_code' => 'sinaptik-social-pro-monthly',
        'amount_gross' => 1499,
    ]));
    $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertOk()
        ->assertJsonPath('duplicate', false);

    $subscription = $subscription->fresh();
    expect($subscription->plan->code)->toBe('pro');
    expect($subscription->period)->toBe('monthly');
    expect($subscription->current_period_end->toIso8601String())->toBe($renewEnd);
    expect(Payment::query()->where('event_id', 'evt_renew')->first()->amount_minor)->toBe(149900);

    [$body, $headers] = sinappWebhookCall(sinappEvent('evt_cancel', 'subscription.canceled', $world->workspace->id, [
        'period_end' => now()->addMonth()->toIso8601String(),
    ]));
    $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertOk();
    $subscription = $subscription->fresh();
    expect($subscription->cancel_at_period_end)->toBeTrue();
    expect($subscription->status)->toBe('active');
    expect($world->workspace->fresh()->status)->toBe('active');

    [$body, $headers] = sinappWebhookCall(sinappEvent('evt_expire', 'subscription.expired', $world->workspace->id));
    $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertOk();

    expect($world->workspace->fresh()->status)->toBe('read_only');
    expect(Post::query()->first()->status)->toBe('paused');
});

it('rejects a bad sinapp webhook signature, an unknown plan, and a missing account', function () {
    $world = World::boot();
    $payload = sinappEvent('evt_bad', 'subscription.activated', $world->workspace->id);
    [$body, $headers] = sinappWebhookCall($payload, secret: 'wrong-secret');
    $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertUnauthorized();

    [$body, $headers] = sinappWebhookCall($payload, time() - 3600);
    $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertUnauthorized();

    [$body, $headers] = sinappWebhookCall(sinappEvent('evt_plan', 'subscription.activated', $world->workspace->id, [
        'plan_code' => 'sinaptik-social-yok-monthly',
    ]));
    $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertStatus(422);
    expect(BillingEvent::query()->where('event_id', 'evt_plan')->exists())->toBeFalse();

    [$body, $headers] = sinappWebhookCall(sinappEvent('evt_missing', 'subscription.activated', 999999));
    $this->call('POST', '/api/webhooks/sinapp-subscriptions', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertNotFound();
});

it('sends checkout to the sinapp purchase page with the email prefilled', function () {
    $world = World::boot();
    config([
        'billing.gateway' => 'sinapp',
        'billing.sinapp.base_url' => 'https://sinapp.com.tr',
        'billing.sinapp.purchase_path' => '/satin-al/sinaptik-social',
    ]);

    $response = $this->actingAs($world->owner)->postJson('/api/billing/checkout', [
        'plan_code' => 'agency',
        'period' => 'monthly',
    ])->assertOk();

    $url = $response->json('url');
    expect($url)->toBe($response->json('redirect_url'));
    expect($url)->toStartWith('https://sinapp.com.tr/satin-al/sinaptik-social?');
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    expect($query['plan'])->toBe('sinaptik-social-agency-monthly');
    expect($query['email'])->toBe($world->owner->email);
    expect($query['account_id'])->toBe((string) $world->workspace->id);
    expect($query['return_url'])->toContain('/uygulama/faturalama?status=ok');

    config(['billing.gateway' => 'fake']);
    $fake = $this->actingAs($world->owner)->postJson('/api/billing/checkout', [
        'plan_code' => 'agency',
        'period' => 'yearly',
    ])->assertOk();
    expect($fake->json('url'))->toContain('/faturalama/sahte?session=');
    expect($fake->json('url'))->toBe($fake->json('redirect_url'));
});
