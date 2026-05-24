<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Organization;
use App\Models\Plan;

/**
 * Stub gateway used in v1. Replace by binding a concrete
 * PayTR / Stripe implementation in AppServiceProvider.
 */
class NullGateway implements PaymentGateway
{
    public function createCheckout(Organization $organization, Plan $plan): array
    {
        return [
            'status' => 'not_configured',
            'message' => 'No payment gateway configured. Set PAYMENT_GATEWAY=paytr|stripe and bind a concrete driver.',
        ];
    }

    public function handleCallback(array $payload): array
    {
        return ['status' => 'not_configured'];
    }
}
