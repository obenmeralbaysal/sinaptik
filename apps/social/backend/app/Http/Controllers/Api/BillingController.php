<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Billing\BillingGateway;
use App\Support\Role;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function plans()
    {
        return response()->json([
            'plans' => Plan::query()->where('is_public', true)->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function subscription(Request $request)
    {
        $workspace = $request->attributes->get('workspace');

        return response()->json([
            'subscription' => Subscription::query()->where('workspace_id', $workspace->id)->with('plan')->latest('id')->first(),
            'payments' => Payment::query()->where('workspace_id', $workspace->id)->latest('id')->limit(50)->get(),
        ]);
    }

    public function checkout(Request $request, BillingGateway $gateway)
    {
        $this->assertOwner($request);
        $data = $request->validate([
            'plan_code' => ['required', 'string'],
            'period' => ['required', 'in:monthly,yearly'],
        ]);
        $workspace = $request->attributes->get('workspace');
        $plan = Plan::query()->where('code', $data['plan_code'])->where('is_active', true)->firstOrFail();
        $current = Subscription::query()->where('workspace_id', $workspace->id)->with('plan')->latest('id')->first();

        if ($current && $current->plan && $plan->priceFor($data['period']) < $current->plan->priceFor($current->period)) {
            $current->update(['pending_plan_id' => $plan->id, 'pending_period' => $data['period']]);

            return response()->json(['downgrade' => true, 'effective_at' => $current->current_period_end]);
        }

        $front = rtrim((string) config('sinsocial.frontend_url'), '/');
        $session = $gateway->createCheckoutSession(
            $workspace,
            $plan,
            $data['period'],
            $request->user(),
            $front.'/uygulama/faturalama?status=ok',
            $front.'/uygulama/faturalama?status=cancel',
        );

        return response()->json($session + ['url' => $session['redirect_url']]);
    }

    private function assertOwner(Request $request): void
    {
        if ($request->attributes->get('membership')->role !== Role::OWNER) {
            abort(403, 'Faturalama yalnızca sahibe açıktır.');
        }
    }
}
