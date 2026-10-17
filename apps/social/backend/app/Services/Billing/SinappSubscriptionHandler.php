<?php

namespace App\Services\Billing;

use App\Models\BillingEvent;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\TrialService;
use App\Support\WorkspaceContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class SinappSubscriptionHandler
{
    public function __construct(
        private SinappCatalog $catalog,
        private TrialService $trials,
    ) {}

    public function handle(array $payload): array
    {
        $eventId = trim((string) ($payload['id'] ?? ''));
        $type = trim((string) ($payload['type'] ?? ''));
        if ($eventId === '' || $type === '') {
            abort(422, 'id ve type zorunludur.');
        }

        try {
            return DB::transaction(function () use ($payload, $eventId, $type) {
                $existing = BillingEvent::query()->where('event_id', $eventId)->lockForUpdate()->first();
                if ($existing) {
                    return ['ok' => true, 'duplicate' => true];
                }

                $data = (array) ($payload['data'] ?? []);
                $workspace = $this->workspace($data);
                app(WorkspaceContext::class)->activate($workspace->id);

                if (in_array($type, ['subscription.activated', 'subscription.renewed'], true)) {
                    $this->resolvePlan($data);
                } elseif (! in_array($type, ['subscription.canceled', 'subscription.expired'], true)) {
                    abort(422, 'Bilinmeyen Sinapp olayı.');
                }

                BillingEvent::query()->create([
                    'event_id' => $eventId,
                    'type' => $type,
                    'payload' => $payload,
                    'processed_at' => now(),
                ]);

                match ($type) {
                    'subscription.activated' => $this->activate($workspace, $data, $eventId, 'Abonelik'),
                    'subscription.renewed' => $this->activate($workspace, $data, $eventId, 'Yenileme'),
                    'subscription.canceled' => $this->cancel($workspace, $data),
                    'subscription.expired' => $this->expire($workspace, $data),
                    default => abort(422, 'Bilinmeyen Sinapp olayı.'),
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

    private function workspace(array $data): Workspace
    {
        $id = trim((string) ($data['account_id'] ?? ''));
        if ($id === '' || ! ctype_digit($id)) {
            abort(422, 'account_id zorunludur.');
        }

        $workspace = Workspace::query()->find((int) $id);
        if (! $workspace) {
            abort(404, 'Çalışma alanı bulunamadı.');
        }

        return $workspace;
    }

    /**
     * @return array{plan: Plan, interval: string}
     */
    private function resolvePlan(array $data): array
    {
        $parsed = $this->catalog->parse((string) ($data['plan_code'] ?? ''));
        if (! $parsed) {
            abort(422, 'Plan kodu tanınmadı.');
        }

        $plan = Plan::query()
            ->where('code', $parsed['plan'])
            ->where('is_public', true)
            ->where('is_active', true)
            ->first();
        if (! $plan) {
            abort(422, 'Plan bulunamadı.');
        }

        return ['plan' => $plan, 'interval' => $parsed['interval']];
    }

    private function activate(Workspace $workspace, array $data, string $eventId, string $description): void
    {
        $resolved = $this->resolvePlan($data);
        $plan = $resolved['plan'];
        $interval = $resolved['interval'];
        $start = $this->moment($data['period_start'] ?? null) ?? now();
        $end = $this->moment($data['period_end'] ?? null) ?? ($interval === 'yearly' ? $start->copy()->addYear() : $start->copy()->addMonth());

        $subscription = Subscription::query()->where('workspace_id', $workspace->id)->latest('id')->first()
            ?? new Subscription(['workspace_id' => $workspace->id]);
        $subscription->fill([
            'plan_id' => $plan->id,
            'status' => 'active',
            'period' => $interval,
            'current_period_start' => $start,
            'current_period_end' => $end,
            'gateway' => 'sinapp',
            'gateway_subscription_id' => (string) ($data['subscription_id'] ?? $subscription->gateway_subscription_id),
            'pending_plan_id' => null,
            'pending_period' => null,
            'cancel_at_period_end' => false,
            'dunning_attempts' => 0,
            'dunning_started_at' => null,
        ])->save();

        $this->trials->markActive($workspace, $subscription);
        $this->payment($workspace, $subscription, $eventId, $description, $data, $plan, $interval);
    }

    private function cancel(Workspace $workspace, array $data): void
    {
        $subscription = $this->requireSubscription($workspace);
        $end = $this->moment($data['period_end'] ?? null) ?? $subscription->current_period_end;
        $subscription->cancel_at_period_end = true;
        $subscription->gateway = 'sinapp';
        $subscription->gateway_subscription_id = (string) ($data['subscription_id'] ?? $subscription->gateway_subscription_id);
        if ($end) {
            $subscription->current_period_end = $end;
        }
        $subscription->save();

        if ($subscription->current_period_end && $subscription->current_period_end->isPast()) {
            $subscription->update(['status' => 'canceled']);
            $this->trials->enterReadOnly($workspace, $subscription, 'canceled');
        }
    }

    private function expire(Workspace $workspace, array $data): void
    {
        $subscription = $this->requireSubscription($workspace);
        $end = $this->moment($data['period_end'] ?? null);
        $subscription->fill([
            'status' => 'canceled',
            'cancel_at_period_end' => false,
            'current_period_end' => $end ?? now(),
            'gateway' => 'sinapp',
            'gateway_subscription_id' => (string) ($data['subscription_id'] ?? $subscription->gateway_subscription_id),
        ])->save();
        $this->trials->enterReadOnly($workspace, $subscription, 'expired');
    }

    private function requireSubscription(Workspace $workspace): Subscription
    {
        $subscription = Subscription::query()->where('workspace_id', $workspace->id)->latest('id')->first();
        if (! $subscription) {
            abort(404, 'Abonelik yok.');
        }

        return $subscription;
    }

    private function payment(Workspace $workspace, Subscription $subscription, string $eventId, string $description, array $data, Plan $plan, string $interval): void
    {
        $gross = isset($data['amount_gross']) && is_numeric($data['amount_gross'])
            ? (int) round(((float) $data['amount_gross']) * 100)
            : $plan->priceFor($interval);
        $currency = strtoupper((string) ($data['currency'] ?? 'TRY'));
        if (strlen($currency) !== 3) {
            $currency = 'TRY';
        }

        Payment::query()->create([
            'workspace_id' => $workspace->id,
            'subscription_id' => $subscription->id,
            'event_id' => $eventId,
            'kind' => 'subscription',
            'status' => 'paid',
            'amount_minor' => $gross,
            'currency' => $currency,
            'description' => $description,
            'gateway_payment_id' => (string) ($data['subscription_id'] ?? ''),
            'meta' => [
                'amount_gross' => $data['amount_gross'] ?? null,
                'amount_net' => $data['amount_net'] ?? null,
                'vat_amount' => $data['vat_amount'] ?? null,
                'billing' => $data['billing'] ?? null,
                'plan_code' => $data['plan_code'] ?? null,
            ],
            'paid_at' => now(),
        ]);
    }

    private function moment(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            abort(422, 'Tarih geçersiz.');
        }
    }
}
