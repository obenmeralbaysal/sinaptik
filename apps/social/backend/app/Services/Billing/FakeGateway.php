<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FakeGateway implements BillingGateway
{
    public function createCheckoutSession(Workspace $workspace, Plan $plan, string $period, User $customer, string $successUrl, string $cancelUrl): array
    {
        $id = 'fake_'.Str::random(16);
        Cache::put('billing.fake.'.$id, [
            'workspace_id' => $workspace->id,
            'plan_code' => $plan->code,
            'period' => $period,
            'email' => $customer->email,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ], now()->addHour());

        $redirect = rtrim((string) config('sinsocial.frontend_url'), '/').'/faturalama/sahte?session='.$id;

        return ['session_id' => $id, 'redirect_url' => $redirect];
    }
}
