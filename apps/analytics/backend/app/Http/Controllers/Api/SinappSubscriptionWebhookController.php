<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Billing\SinappSignature;
use App\Services\Billing\SinappSubscriptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SinappSubscriptionWebhookController extends Controller
{
    public function __invoke(Request $request, SinappSignature $signature, SinappSubscriptionHandler $handler): JsonResponse
    {
        if ((string) config('billing.sinapp.secret') === '') {
            return response()->json(['error' => 'temporarily_unavailable'], 503);
        }

        $signature->assertWebhook($request);

        return response()->json($handler->handle($request->json()->all()));
    }
}
