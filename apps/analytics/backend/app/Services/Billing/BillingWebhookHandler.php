<?php

namespace App\Services\Billing;

use App\Billing\ProductCode;
use App\Models\BillingEvent;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\TrialService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class BillingWebhookHandler
{
    public function __construct(
        private TrialService $trials,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, duplicate: bool}
     */
    public function handle(array $payload): array
    {
        $eventId = (string) ($payload['event_id'] ?? '');
        $type = (string) ($payload['type'] ?? '');
        if ($eventId === '' || $type === '') {
            abort(422, 'event_id ve type zorunludur.');
        }

        try {
            return DB::transaction(function () use ($payload, $eventId, $type) {
                $existing = BillingEvent::query()->where('event_id', $eventId)->first();
                if ($existing) {
                    return ['ok' => true, 'duplicate' => true];
                }

                BillingEvent::query()->create([
                    'event_id' => $eventId,
                    'type' => $type,
                    'payload' => $payload,
                    'processed_at' => now(),
                ]);

                $data = (array) ($payload['data'] ?? []);
                $workspaceId = (int) ($data['external_reference'] ?? 0);
                $workspace = Workspace::query()->find($workspaceId);
                if (! $workspace) {
                    abort(404, 'Çalışma alanı bulunamadı.');
                }

                $subscription = $this->subscription($workspace, $data);

                match ($type) {
                    config('billing.events.activated') => $this->activate($workspace, $subscription, $data, $eventId),
                    config('billing.events.renewed') => $this->renew($workspace, $subscription, $data, $eventId),
                    config('billing.events.payment_failed') => $this->failed($workspace, $subscription, $data, $eventId),
                    config('billing.events.canceled') => $this->canceled($subscription, $data),
                    config('billing.events.refunded') => $this->refunded($workspace, $subscription, $data, $eventId),
                    config('billing.events.credit_pack') => $this->creditPack($workspace, $data, $eventId),
                    config('billing.events.addon') => $this->addon($workspace, $data, $eventId),
                    default => null,
                };

                return ['ok' => true, 'duplicate' => false];
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (BillingEvent::query()->where('event_id', $eventId)->exists()) {
                return ['ok' => true, 'duplicate' => true];
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function subscription(Workspace $workspace, array $data): ?Subscription
    {
        $product = array_key_exists('product_code', $data)
            ? ProductCode::resolve($data, null)
            : ProductCode::configured();

        return Subscription::query()
            ->where('workspace_id', $workspace->id)
            ->where('product_code', $product)
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function activate(Workspace $workspace, ?Subscription $subscription, array $data, string $eventId): void
    {
        $plan = $this->plan((string) ($data['plan_code'] ?? ''));
        $period = ($data['period'] ?? $plan->interval) === 'yearly' ? 'yearly' : 'monthly';
        $end = isset($data['current_period_end']) ? now()->parse($data['current_period_end']) : ($period === 'yearly' ? now()->addYear() : now()->addMonth());
        $product = array_key_exists('product_code', $data)
            ? ProductCode::resolve($data, null)
            : ProductCode::configured();
        $providerId = trim((string) ($data['gateway_subscription_id'] ?? ''));
        if ($providerId === '') {
            $providerId = (string) ($subscription?->provider_subscription_id ?: $eventId);
        }

        $subscription ??= new Subscription([
            'workspace_id' => $workspace->id,
            'product_code' => $product,
        ]);
        $subscription->fill([
            'workspace_id' => $workspace->id,
            'plan_id' => $plan->id,
            'product_code' => $product,
            'provider_subscription_id' => $providerId,
            'status' => 'active',
            'period' => $period,
            'period_start' => now(),
            'period_end' => $end,
            'gateway' => 'sinapp_hub',
            'pending_plan_id' => null,
            'pending_period' => null,
            'cancel_at_period_end' => false,
            'dunning_attempts' => 0,
            'dunning_started_at' => null,
        ])->save();

        $this->trials->markActive($workspace, $subscription);
        $this->payment($workspace, $subscription, $plan, $eventId, 'subscription', 'paid', (int) ($data['amount_minor'] ?? $plan->price_gross_cents), 'Abonelik');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renew(Workspace $workspace, ?Subscription $subscription, array $data, string $eventId): void
    {
        if (! $subscription) {
            abort(404, 'Abonelik yok.');
        }
        $end = isset($data['current_period_end']) ? now()->parse($data['current_period_end']) : now()->addMonth();
        $subscription->update([
            'status' => 'active',
            'period_start' => now(),
            'period_end' => $end,
            'dunning_attempts' => 0,
            'dunning_started_at' => null,
        ]);
        $this->trials->markActive($workspace, $subscription);
        $this->payment($workspace, $subscription, $subscription->plan, $eventId, 'subscription', 'paid', (int) ($data['amount_minor'] ?? 0), 'Yenileme');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function failed(Workspace $workspace, ?Subscription $subscription, array $data, string $eventId): void
    {
        if (! $subscription) {
            return;
        }
        $subscription->dunning_attempts = (int) $subscription->dunning_attempts + 1;
        $subscription->dunning_started_at ??= now();
        $subscription->status = 'past_due';
        $subscription->save();
        $this->payment($workspace, $subscription, $subscription->plan, $eventId, 'subscription', 'failed', (int) ($data['amount_minor'] ?? 0), 'Başarısız tahsilat');
        if ($this->trials->dunningExhausted($subscription)) {
            $this->trials->enterReadOnly($workspace, $subscription, 'dunning');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function canceled(?Subscription $subscription, array $data): void
    {
        if (! $subscription) {
            return;
        }
        $immediate = (bool) ($data['immediate'] ?? false);
        $subscription->update([
            'status' => $immediate ? 'canceled' : $subscription->status,
            'cancel_at_period_end' => ! $immediate,
            'period_end' => $immediate ? now() : $subscription->period_end,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function refunded(Workspace $workspace, ?Subscription $subscription, array $data, string $eventId): void
    {
        $this->payment($workspace, $subscription, $subscription?->plan, $eventId, 'refund', 'refunded', (int) ($data['amount_minor'] ?? 0), 'İade');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function creditPack(Workspace $workspace, array $data, string $eventId): void
    {
        $this->payment($workspace, null, null, $eventId, 'credit_pack', 'paid', (int) ($data['amount_minor'] ?? 0), 'Kredi paketi');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function addon(Workspace $workspace, array $data, string $eventId): void
    {
        $code = (string) ($data['addon_code'] ?? '');
        $this->payment($workspace, null, null, $eventId, 'addon', 'paid', (int) ($data['amount_minor'] ?? 0), 'Ek paket '.$code);
    }

    private function plan(string $code): Plan
    {
        $plan = Plan::query()->where('code', $code)->where('is_active', true)->first();
        if (! $plan) {
            abort(422, 'Plan bulunamadı.');
        }

        return $plan;
    }

    private function payment(Workspace $workspace, ?Subscription $subscription, ?Plan $plan, string $eventId, string $kind, string $status, int $amount, string $description): void
    {
        Payment::query()->create([
            'workspace_id' => $workspace->id,
            'plan_id' => $plan?->id,
            'subscription_id' => $subscription?->id,
            'event_id' => $eventId,
            'kind' => $kind,
            'status' => $status,
            'amount_cents' => $amount,
            'currency' => 'TRY',
            'provider' => 'sinapp_hub',
            'description' => $description,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);
    }
}
