<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;

class SinappGateway implements BillingGateway
{
    public function __construct(private SinappCatalog $catalog) {}

    public function createCheckoutSession(Workspace $workspace, Plan $plan, string $period, User $customer, string $successUrl, string $cancelUrl): array
    {
        $base = rtrim((string) config('billing.sinapp.base_url'), '/');
        $path = '/'.ltrim((string) config('billing.sinapp.purchase_path'), '/');
        $query = http_build_query([
            'plan' => $this->catalog->code($plan->code, $period),
            'email' => $customer->email,
            'account_id' => $workspace->id,
            'return_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        return [
            'session_id' => 'sinapp-'.$workspace->id.'-'.$plan->code.'-'.$period,
            'redirect_url' => $base.$path.'?'.$query,
        ];
    }
}
