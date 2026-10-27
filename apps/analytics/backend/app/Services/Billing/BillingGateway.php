<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;

interface BillingGateway
{
    /**
     * @return array{session_id: string, redirect_url: string}
     */
    public function createCheckoutSession(Workspace $workspace, Plan $plan, string $period, User $customer, string $successUrl, string $cancelUrl): array;
}
