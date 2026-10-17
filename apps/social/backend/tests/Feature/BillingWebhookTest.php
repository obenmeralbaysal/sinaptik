<?php

use App\Models\Payment;
use App\Models\Subscription;
use Tests\Support\World;

function signedBilling(array $payload, ?string $secret = null, ?int $timestamp = null): array
{
    $body = json_encode($payload);
    $timestamp ??= time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret ?? 'test-secret');

    return [$body, [
        'X-Integration-Key' => 'test-key',
        'X-Timestamp' => (string) $timestamp,
        'X-Signature' => $signature,
        'Content-Type' => 'application/json',
    ]];
}

it('rejects a bad billing signature and an old timestamp', function () {
    $world = World::boot();
    $payload = [
        'event_id' => 'evt_bad',
        'type' => 'subscription.activated',
        'data' => ['external_reference' => (string) $world->workspace->id, 'plan_code' => 'pro', 'period' => 'monthly'],
    ];
    [$body, $headers] = signedBilling($payload, 'wrong-secret');
    $this->call('POST', '/api/webhooks/billing/sinapp', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertUnauthorized();

    [$body, $headers] = signedBilling($payload, 'test-secret', time() - 3600);
    $this->call('POST', '/api/webhooks/billing/sinapp', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertUnauthorized();
});

it('activates a subscription once for a repeated event id', function () {
    $world = World::boot();
    $payload = [
        'event_id' => 'evt_1',
        'type' => 'subscription.activated',
        'data' => [
            'external_reference' => (string) $world->workspace->id,
            'plan_code' => 'agency',
            'period' => 'yearly',
            'amount_minor' => 3999000,
            'gateway_subscription_id' => 'sub_1',
            'current_period_end' => now()->addYear()->toIso8601String(),
        ],
    ];
    [$body, $headers] = signedBilling($payload);
    $this->call('POST', '/api/webhooks/billing/sinapp', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertOk()
        ->assertJsonPath('duplicate', false);
    $this->call('POST', '/api/webhooks/billing/sinapp', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertOk()
        ->assertJsonPath('duplicate', true);

    $subscription = Subscription::query()->where('workspace_id', $world->workspace->id)->latest('id')->first();
    expect($subscription->status)->toBe('active');
    expect($subscription->plan->code)->toBe('agency');
    expect(Payment::query()->where('event_id', 'evt_1')->count())->toBe(1);
});
