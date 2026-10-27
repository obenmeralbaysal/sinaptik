<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function store(Request $request, PaymentGateway $gateway): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($workspace instanceof Workspace, 400);

        $data = $request->validate([
            'plan_code' => ['required', 'string'],
        ]);

        $plan = Plan::query()->where('code', $data['plan_code'])->where('is_active', true)->first();
        if (! $plan) {
            return response()->json(['message' => 'Plan bulunamadı.'], 404);
        }

        return response()->json($gateway->createCheckout($workspace, $plan));
    }
}
