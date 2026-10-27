<?php

namespace App\Billing;

use App\Contracts\SubscriptionProvider;
use App\Models\Plan;
use App\Models\Workspace;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LimitGuard
{
    public function __construct(private readonly SubscriptionProvider $subscriptions) {}

    public function ensureActive(Workspace $workspace): Plan
    {
        $plan = $this->subscriptions->activePlan($workspace);
        if (! $plan) {
            throw new HttpException(402, 'Aktif abonelik yok.');
        }

        return $plan;
    }

    public function ensureCanAddSite(Workspace $workspace): Plan
    {
        $plan = $this->ensureActive($workspace);
        $limit = $plan->site_limit;

        if ($limit !== null && $workspace->sites()->count() >= $limit) {
            throw new HttpException(403, 'Site limiti doldu.');
        }

        return $plan;
    }
}
