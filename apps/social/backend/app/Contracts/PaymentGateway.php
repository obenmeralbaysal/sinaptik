<?php

namespace App\Contracts;

use App\Models\Organization;
use App\Models\Plan;

interface PaymentGateway
{
    public function createCheckout(Organization $organization, Plan $plan): array;

    public function handleCallback(array $payload): array;
}
