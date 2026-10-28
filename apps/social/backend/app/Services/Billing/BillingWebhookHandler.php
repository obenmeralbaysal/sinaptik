<?php

namespace App\Services\Billing;

use App\Models\BillingEvent;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Models\WorkspaceAddon;
use App\Services\Credits\CreditLedger;
use App\Services\TrialService;
use App\Support\WorkspaceContext;
use Illuminate\Support\Facades\DB;

class BillingWebhookHandler
{
    public function __construct(
        private TrialService $trials,
        private CreditLedger $credits,
    ) {}

    public function handle(array $payload): array
    {
        $eventId = (string) ($payload['event_id'] ?? '');
        $type = (string) ($payload['type'] ?? '');
        if ($eventId === '' || $type === '') {
            abort(422, 'event_id ve type zorunludur.');
        }

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

            app(WorkspaceContext::class)->activate($workspace->id);
            $subscription = Subscription::query()->where('workspace_id', $workspace->id)->latest('id')->first();

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
    }

    private function activate(Workspace $workspace, ?Subscription $subscription, array $data, string $eventId): void
    {
        $plan = Plan::query()->where('code', $data['plan_code'] ?? '')->firstOrFail();
        $period = ($data['period'] ?? 'monthly') === 'yearly' ? 'yearly' : 'monthly';
        $end = isset($data['current_period_end']) ? now()->parse($data['current_period_end']) : ($period === 'yearly' ? now()->addYear() : now()->addMonth());

        $subscription ??= new Subscription(['workspace_id' => $workspace->id]);
        $subscription->fill([
            'plan_id' => $plan->id,
            'status' => 'active',
            'period' => $period,
            'current_period_start' => now(),
            'current_period_end' => $end,
            'gateway' => 'sinapp_hub',
            'gateway_subscription_id' => $data['gateway_subscription_id'] ?? $subscription->gateway_subscription_id,
            'pending_plan_id' => null,
            'cancel_at_period_end' => false,
        ])->save();

        $this->trials->markActive($workspace, $subscription);
        $this->payment($workspace, $subscription, $eventId, 'subscription', 'paid', (int) ($data['amount_minor'] ?? $plan->priceFor($period)), 'Abonelik');
    }

    private function renew(Workspace $workspace, ?Subscription $subscription, array $data, string $eventId): void
    {
        if (! $subscription) {
            abort(404, 'Abonelik yok.');
        }
        $end = isset($data['current_period_end']) ? now()->parse($data['current_period_end']) : now()->addMonth();
        $subscription->update([
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => $end,
            'dunning_attempts' => 0,
            'dunning_started_at' => null,
        ]);
        $this->trials->markActive($workspace, $subscription);
        $this->payment($workspace, $subscription, $eventId, 'subscription', 'paid', (int) ($data['amount_minor'] ?? 0), 'Yenileme');
    }

    private function failed(Workspace $workspace, ?Subscription $subscription, array $data, string $eventId): void
    {
        if (! $subscription) {
            return;
        }
        $subscription->dunning_attempts = (int) $subscription->dunning_attempts + 1;
        $subscription->dunning_started_at ??= now();
        $subscription->status = 'past_due';
        $subscription->save();
        $this->payment($workspace, $subscription, $eventId, 'subscription', 'failed', (int) ($data['amount_minor'] ?? 0), 'Başarısız tahsilat');
        if ($this->trials->dunningExhausted($subscription)) {
            $this->trials->enterReadOnly($workspace, $subscription, 'dunning');
        }
    }

    private function canceled(?Subscription $subscription, array $data): void
    {
        if (! $subscription) {
            return;
        }
        $immediate = (bool) ($data['immediate'] ?? false);
        $subscription->update([
            'status' => $immediate ? 'canceled' : $subscription->status,
            'cancel_at_period_end' => ! $immediate,
        ]);
        if ($immediate) {
            $subscription->update(['status' => 'canceled', 'current_period_end' => now()]);
        }
    }

    private function refunded(Workspace $workspace, ?Subscription $subscription, array $data, string $eventId): void
    {
        $this->payment($workspace, $subscription, $eventId, 'refund', 'refunded', (int) ($data['amount_minor'] ?? 0), 'İade');
    }

    private function creditPack(Workspace $workspace, array $data, string $eventId): void
    {
        $type = (string) ($data['pack'] ?? 'x_credit');
        $credits = (int) ($data['credits'] ?? 0);
        $this->credits->post($workspace, $type, $credits, 'purchase', $eventId);
        $this->payment($workspace, null, $eventId, 'credit_pack', 'paid', (int) ($data['amount_minor'] ?? 0), 'Kredi paketi');
    }

    private function addon(Workspace $workspace, array $data, string $eventId): void
    {
        $code = (string) ($data['addon_code'] ?? '');
        $qty = (int) ($data['quantity'] ?? 1);
        $addon = WorkspaceAddon::query()->firstOrNew([
            'workspace_id' => $workspace->id,
            'code' => $code,
        ]);
        $addon->quantity = (int) $addon->quantity + $qty;
        $addon->save();
        $this->payment($workspace, null, $eventId, 'addon', 'paid', (int) ($data['amount_minor'] ?? 0), 'Ek paket '.$code);
    }

    private function payment(Workspace $workspace, ?Subscription $subscription, string $eventId, string $kind, string $status, int $amount, string $description): void
    {
        Payment::query()->create([
            'workspace_id' => $workspace->id,
            'subscription_id' => $subscription?->id,
            'event_id' => $eventId,
            'kind' => $kind,
            'status' => $status,
            'amount_minor' => $amount,
            'currency' => 'TRY',
            'description' => $description,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);
    }
}
