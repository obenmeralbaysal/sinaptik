<?php

namespace App\Contracts;

use App\Models\Plan;
use App\Models\Workspace;

interface PaymentGateway
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(Workspace $workspace, Plan $plan): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handleCallback(array $payload): array;
}
