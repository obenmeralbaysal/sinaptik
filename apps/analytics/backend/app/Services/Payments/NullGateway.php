<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Plan;
use App\Models\Workspace;

class NullGateway implements PaymentGateway
{
    public function createCheckout(Workspace $workspace, Plan $plan): array
    {
        return [
            'status' => 'not_configured',
            'message' => 'Ödeme geçidi yapılandırılmadı.',
        ];
    }

    public function handleCallback(array $payload): array
    {
        return ['status' => 'not_configured'];
    }
}
