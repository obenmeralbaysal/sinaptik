<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\Workspace;

class TrialService
{
    public function sync(Workspace $workspace): Workspace
    {
        $subscription = Subscription::query()->where('workspace_id', $workspace->id)->latest('id')->first();
        if (! $subscription) {
            return $workspace;
        }

        if ($subscription->status === 'trialing' && $subscription->trial_ends_at?->isPast()) {
            $this->enterReadOnly($workspace, $subscription, 'trial_expired');
        }

        if ($subscription->status === 'canceled' && $subscription->period_end?->isPast()) {
            $this->enterReadOnly($workspace, $subscription, 'canceled');
        }

        if ($subscription->cancel_at_period_end && $subscription->period_end?->isPast() && in_array($subscription->status, ['active', 'trialing'], true)) {
            $this->enterReadOnly($workspace, $subscription, 'canceled');
        }

        if ($subscription->status === 'past_due' && $this->dunningExhausted($subscription)) {
            $this->enterReadOnly($workspace, $subscription, 'dunning');
        }

        if ($subscription->pending_plan_id && $subscription->period_end?->isPast()) {
            $subscription->update([
                'plan_id' => $subscription->pending_plan_id,
                'period' => $subscription->pending_period ?: $subscription->period,
                'pending_plan_id' => null,
                'pending_period' => null,
            ]);
        }

        return $workspace->fresh();
    }

    public function enterReadOnly(Workspace $workspace, Subscription $subscription, string $reason): void
    {
        $subscription->update([
            'status' => 'read_only',
            'period_end' => $subscription->period_end ?? now(),
        ]);
    }

    public function markActive(Workspace $workspace, Subscription $subscription): void
    {
        $subscription->update([
            'status' => 'active',
            'dunning_attempts' => 0,
            'dunning_started_at' => null,
        ]);
    }

    public function dunningExhausted(Subscription $subscription): bool
    {
        $attempts = (int) config('billing.dunning_attempts');
        $days = (int) config('billing.dunning_days');

        if ($subscription->dunning_attempts >= $attempts) {
            return true;
        }

        return $subscription->dunning_started_at !== null
            && $subscription->dunning_started_at->copy()->addDays($days)->isPast();
    }
}
