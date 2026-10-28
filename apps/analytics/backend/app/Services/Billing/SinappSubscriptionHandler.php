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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class SinappSubscriptionHandler
{
    public function __construct(
        private SinappCatalog $catalog,
        private TrialService $trials,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, duplicate: bool}
     */
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function workspace(array $data): Workspace
    {
        $id = trim((string) ($data['account_id'] ?? ''));
        if ($id !== '' && ctype_digit($id)) {
            $workspace = Workspace::query()->find((int) $id);
            if (! $workspace) {
                abort(404, 'Çalışma alanı bulunamadı.');
            }

            return $workspace;
        }

        $slug = trim((string) ($data['workspace_slug'] ?? ''));
        if ($slug === '') {
            abort(422, 'account_id zorunludur.');
        }

        $workspace = Workspace::query()->where('slug', $slug)->first();
        if (! $workspace) {
            abort(404, 'Çalışma alanı bulunamadı.');
        }

        return $workspace;
    }

    /**
     * @param  array<string, mixed>  $data
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
            ->where('is_active', true)
            ->first();
        if (! $plan || ! $plan->is_public) {
            abort(422, 'Plan bulunamadı.');
        }

        return ['plan' => $plan, 'interval' => $parsed['interval']];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function activate(Workspace $workspace, array $data, string $eventId, string $description): void
    {
        $resolved = $this->resolvePlan($data);
        $plan = $resolved['plan'];
        $interval = $resolved['interval'];
        [$product, $subscription] = $this->locate($workspace, $data);
        $start = $this->moment($data['period_start'] ?? null) ?? now();
        $end = $this->moment($data['period_end'] ?? null) ?? ($interval === 'yearly' ? $start->copy()->addYear() : $start->copy()->addMonth());
        $providerId = trim((string) ($data['subscription_id'] ?? ''));
        if ($providerId === '') {
            $providerId = (string) ($subscription->provider_subscription_id ?: $eventId);
        }

        $subscription->fill([
            'workspace_id' => $workspace->id,
            'plan_id' => $plan->id,
            'product_code' => $product,
            'provider_subscription_id' => $providerId,
            'status' => 'active',
            'period' => $interval,
            'period_start' => $start,
            'period_end' => $end,
            'gateway' => 'sinapp',
            'pending_plan_id' => null,
            'pending_period' => null,
            'cancel_at_period_end' => false,
            'dunning_attempts' => 0,
            'dunning_started_at' => null,
        ])->save();

        $this->trials->markActive($workspace, $subscription);
        $this->payment($workspace, $subscription, $eventId, $description, $data, $plan);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function cancel(Workspace $workspace, array $data): void
    {
        [, $subscription] = $this->locate($workspace, $data);
        if (! $subscription->exists) {
            abort(404, 'Abonelik yok.');
        }

        $end = $this->moment($data['period_end'] ?? null) ?? $subscription->period_end;
        $providerId = trim((string) ($data['subscription_id'] ?? ''));
        $subscription->cancel_at_period_end = true;
        $subscription->gateway = 'sinapp';
        if ($providerId !== '') {
            $subscription->provider_subscription_id = $providerId;
        }
        if ($end) {
            $subscription->period_end = $end;
        }
        $subscription->save();

        if ($subscription->period_end && $subscription->period_end->isPast()) {
            $subscription->update(['status' => 'canceled']);
            $this->trials->enterReadOnly($workspace, $subscription, 'canceled');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function expire(Workspace $workspace, array $data): void
    {
        [, $subscription] = $this->locate($workspace, $data);
        if (! $subscription->exists) {
            abort(404, 'Abonelik yok.');
        }

        $end = $this->moment($data['period_end'] ?? null);
        $providerId = trim((string) ($data['subscription_id'] ?? ''));
        $subscription->fill([
            'status' => 'canceled',
            'cancel_at_period_end' => false,
            'period_end' => $end ?? now(),
            'gateway' => 'sinapp',
        ]);
        if ($providerId !== '') {
            $subscription->provider_subscription_id = $providerId;
        }
        $subscription->save();
        $this->trials->enterReadOnly($workspace, $subscription, 'expired');
    }

    /**
     * Latest row for the resolved product. A missing product_code keeps the
     * current product and never substitutes another plan.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: Subscription}
     */
    private function locate(Workspace $workspace, array $data): array
    {
        $latest = Subscription::query()->where('workspace_id', $workspace->id)->latest('id')->first();
        $product = ProductCode::resolve($data, $latest?->product_code);
        $subscription = Subscription::query()
            ->where('workspace_id', $workspace->id)
            ->where('product_code', $product)
            ->latest('id')
            ->first();

        return [$product, $subscription ?? new Subscription([
            'workspace_id' => $workspace->id,
            'product_code' => $product,
        ])];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function payment(Workspace $workspace, Subscription $subscription, string $eventId, string $description, array $data, Plan $plan): void
    {
        $gross = isset($data['amount_gross']) && is_numeric($data['amount_gross'])
            ? (int) round(((float) $data['amount_gross']) * 100)
            : (int) $plan->price_gross_cents;
        $currency = strtoupper((string) ($data['currency'] ?? 'TRY'));
        if (strlen($currency) !== 3) {
            $currency = 'TRY';
        }

        Payment::query()->create([
            'workspace_id' => $workspace->id,
            'plan_id' => $plan->id,
            'subscription_id' => $subscription->id,
            'event_id' => $eventId,
            'kind' => 'subscription',
            'status' => 'paid',
            'amount_cents' => $gross,
            'currency' => $currency,
            'provider' => 'sinapp',
            'description' => $description,
            'transaction_id' => (string) ($data['subscription_id'] ?? ''),
            'gateway_payment_id' => (string) ($data['subscription_id'] ?? ''),
            'meta' => [
                'amount_gross' => $data['amount_gross'] ?? null,
                'amount_net' => $data['amount_net'] ?? null,
                'vat_amount' => $data['vat_amount'] ?? null,
                'billing' => $data['billing'] ?? null,
                'plan_code' => $data['plan_code'] ?? null,
            ],
            'raw_payload' => $data,
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
