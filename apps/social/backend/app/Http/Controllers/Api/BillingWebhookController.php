<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingWebhookHandler;
use App\Services\Billing\HubSignature;
use Illuminate\Http\Request;

class BillingWebhookController extends Controller
{
    public function __invoke(Request $request, HubSignature $signature, BillingWebhookHandler $handler)
    {
        $signature->assertValid($request);

        return response()->json($handler->handle($request->json()->all()));
    }
}
