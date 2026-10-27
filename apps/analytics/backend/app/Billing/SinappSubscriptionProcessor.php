<?php

namespace App\Billing;

use App\Models\Plan;
use App\Models\SinappWebhookEvent;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Database\UniqueConstraintViolationException;

final class SinappSubscriptionProcessor
{
    /** @var list<string> */
    public const TYPES = [
        'subscription.activated',
        'subscription.renewed',
        'subscription.canceled',
        'subscription.expired',
    ];

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, event_id: string}
     */
    public function accept(array $payload): array
    {
        $id = trim((string) ($payload['id'] ?? ''));
        if ($id === '' || strlen($id) > 191) {
            throw new \InvalidArgumentException('invalid event id');
        }

        $existing = SinappWebhookEvent::query()->find($id);
        if ($existing?->processed_at !== null) {
            return ['status' => 'duplicate', 'event_id' => $id];
        }

        if (! $existing) {
            $existing = new SinappWebhookEvent([
                'id' => $id,
                'type' => trim((string) ($payload['type'] ?? '')) ?: null,
                'payload' => $payload,
            ]);
            try {
                $existing->save();
            } catch (UniqueConstraintViolationException) {
                $existing = SinappWebhookEvent::query()->find($id);
                if ($existing?->processed_at !== null) {
                    return ['status' => 'duplicate', 'event_id' => $id];
                }
            }
        }

        $type = trim((string) ($payload['type'] ?? ''));
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        if (! in_array($type, self::TYPES, true)) {
            $existing->forceFill(['last_error' => 'ignored', 'processed_at' => now()])->save();

            return ['status' => 'ignored', 'event_id' => $id];
        }

        $this->apply($existing, $type, $data);

        return ['status' => 'accepted', 'event_id' => $id];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function apply(SinappWebhookEvent $event, string $type, array $data): void
    {
        $subscriptionId = trim((string) ($data['subscription_id'] ?? ''));
        $planCode = trim((string) ($data['plan_code'] ?? ''));
        $slug = trim((string) ($data['workspace_slug'] ?? ''));

        if ($subscriptionId === '' || $slug === '') {
            $event->forceFill(['last_error' => 'invalid_payload', 'processed_at' => now()])->save();

            return;
        }

        $workspace = Workspace::query()->where('slug', $slug)->first();
        $plan = Plan::query()->where('code', $planCode)->where('is_active', true)->first();

        if (! $workspace || ! $plan) {
            $event->forceFill([
                'last_error' => $workspace ? 'unknown_plan' : 'workspace_not_found',
                'processed_at' => now(),
            ])->save();

            return;
        }

        $status = match ($type) {
            'subscription.canceled' => 'canceled_until',
            'subscription.expired' => 'expired',
            default => 'active',
        };

        $periodEnd = $data['period_end'] ?? null;

        Subscription::query()->updateOrCreate(
            ['provider_subscription_id' => $subscriptionId],
            [
                'workspace_id' => $workspace->id,
                'plan_id' => $plan->id,
                'status' => $status,
                'period_end' => is_string($periodEnd) && $periodEnd !== '' ? $periodEnd : null,
            ],
        );

        $event->forceFill(['last_error' => null, 'processed_at' => now()])->save();
    }
}
