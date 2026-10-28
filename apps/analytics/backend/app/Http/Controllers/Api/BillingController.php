<?php

namespace App\Http\Controllers\Api;

use App\Billing\ProductCode;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\Billing\BillingGateway;
use App\Services\Billing\SinappCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function plans(): JsonResponse
    {
        return response()->json([
            'plans' => Plan::query()->where('is_public', true)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function subscription(Request $request): JsonResponse
    {
        $workspace = $this->workspace($request);

        return response()->json([
            'subscription' => Subscription::query()
                ->where('workspace_id', $workspace->id)
                ->where('product_code', ProductCode::configured())
                ->with('plan')
                ->latest('id')
                ->first(),
            'payments' => Payment::query()->where('workspace_id', $workspace->id)->latest('id')->limit(50)->get(),
        ]);
    }

    public function checkout(Request $request, BillingGateway $gateway, SinappCatalog $catalog): JsonResponse
    {
        $this->assertOwner($request);
        $data = $request->validate([
            'plan_code' => ['required', 'string'],
            'period' => ['required', 'in:monthly,yearly'],
        ]);
        $workspace = $this->workspace($request);
        $plan = Plan::query()->where('code', $data['plan_code'])->where('is_active', true)->first()
            ?? Plan::query()->where('code', $catalog->code($data['plan_code'], $data['period']))->where('is_active', true)->first();
        if (! $plan || $plan->interval !== $data['period']) {
            return response()->json(['message' => 'Plan bulunamadı.'], 404);
        }

        $current = Subscription::query()
            ->where('workspace_id', $workspace->id)
            ->where('product_code', ProductCode::configured())
            ->with('plan')
            ->latest('id')
            ->first();

        if ($current && $current->plan && $plan->price_gross_cents < $current->plan->price_gross_cents) {
            $current->update(['pending_plan_id' => $plan->id, 'pending_period' => $data['period']]);

            return response()->json(['downgrade' => true, 'effective_at' => $current->period_end]);
        }

        $front = rtrim((string) config('billing.frontend_url'), '/');
        $session = $gateway->createCheckoutSession(
            $workspace,
            $plan,
            $data['period'],
            $request->user(),
            $front.'/pricing?status=ok',
            $front.'/pricing?status=cancel',
        );

        return response()->json($session + ['url' => $session['redirect_url']]);
    }

    private function workspace(Request $request): Workspace
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($workspace instanceof Workspace, 400);

        return $workspace;
    }

    private function assertOwner(Request $request): void
    {
        $workspace = $this->workspace($request);
        $role = $workspace->users()->where('users.id', $request->user()->id)->first()?->pivot?->role;
        if ($role !== 'owner') {
            abort(403, 'Faturalama yalnızca sahibe açıktır.');
        }
    }
}
