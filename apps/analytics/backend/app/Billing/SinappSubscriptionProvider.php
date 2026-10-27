<?php

namespace App\Billing;

use App\Contracts\SubscriptionProvider;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;

class SinappSubscriptionProvider implements SubscriptionProvider
{
    public function activePlan(Workspace $workspace): ?Plan
    {
        $subscription = Subscription::query()
            ->where('workspace_id', $workspace->id)
            ->currentlyActive()
            ->with('plan')
            ->latest('id')
            ->first();

        $plan = $subscription?->plan;

        if (! $plan || ! $plan->is_active) {
            return null;
        }

        return $plan;
    }

    public function hasFeature(Workspace $workspace, string $feature): bool
    {
        $plan = $this->activePlan($workspace);
        if (! $plan) {
            return false;
        }

        $features = $plan->features ?? [];

        return in_array($feature, $features, true);
    }
}
