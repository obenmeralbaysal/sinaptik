<?php

namespace App\Contracts;

use App\Models\Plan;
use App\Models\Workspace;

interface SubscriptionProvider
{
    public function activePlan(Workspace $workspace): ?Plan;

    public function hasFeature(Workspace $workspace, string $feature): bool;
}
