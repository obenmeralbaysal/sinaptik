<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingWebhookHandler;
use App\Services\Billing\HubSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingWebhookController extends Controller
{
    public function __invoke(Request $request, HubSignature $signature, BillingWebhookHandler $handler): JsonResponse
    {
        if ((string) config('billing.hub.secret') === '' || (string) config('billing.hub.integration_key') === '') {
            return response()->json(['error' => 'temporarily_unavailable'], 503);
        }

        $signature->assertValid($request);

        return response()->json($handler->handle($request->json()->all()));
    }
}
