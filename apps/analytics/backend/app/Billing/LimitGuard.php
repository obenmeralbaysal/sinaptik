<?php

namespace App\Billing;

use App\Contracts\SubscriptionProvider;
use App\Models\Plan;
use App\Models\Workspace;
use App\Services\UsageMeter;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LimitGuard
{
    public function __construct(
        private readonly SubscriptionProvider $subscriptions,
        private readonly UsageMeter $usage,
    ) {}

    public function plan(Workspace $workspace): ?Plan
    {
        return $this->subscriptions->activePlan($workspace);
    }

    public function used(Workspace $workspace, string $metric): int
    {
        return match ($metric) {
            'sites' => $workspace->sites()->count(),
            default => $this->usage->value($workspace, $metric),
        };
    }

    public function ensureActive(Workspace $workspace): Plan
    {
        $plan = $this->plan($workspace);
        if (! $plan) {
            throw new HttpException(402, 'Aktif abonelik yok.');
        }

        return $plan;
    }

    public function ensureCanAddSite(Workspace $workspace): Plan
    {
        $plan = $this->ensureActive($workspace);
        $limit = $plan->site_limit;

        if ($limit !== null && $this->used($workspace, 'sites') >= $limit) {
            throw new HttpException(403, 'Site limiti doldu.');
        }

        return $plan;
    }
}
