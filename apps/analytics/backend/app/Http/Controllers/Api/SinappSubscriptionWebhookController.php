<?php

namespace App\Http\Controllers\Api;

use App\Billing\SinappSubscriptionProcessor;
use App\Billing\WebhookSignature;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SinappSubscriptionWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookSignature $signature,
        private readonly SinappSubscriptionProcessor $processor,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.sinapp.secret', '');
        if ($secret === '') {
            return response()->json(['error' => 'temporarily_unavailable'], 503);
        }

        $raw = $request->getContent();
        $timestamp = trim((string) $request->header('X-Sinapp-Timestamp', ''));
        $tolerance = (int) config('services.sinapp.tolerance', 300);

        if (! $this->signature->timestampFresh($timestamp, $tolerance)
            || ! $this->signature->verify($secret, $timestamp, $raw, (string) $request->header('X-Sinapp-Signature', ''))) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $header = trim((string) $request->header('X-Sinapp-Event', ''));
        if ($header === '') {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return response()->json(['error' => 'invalid_payload'], 400);
        }

        $bodyType = trim((string) ($payload['type'] ?? ''));
        if ($bodyType !== '' && ! hash_equals($bodyType, $header)) {
            return response()->json(['error' => 'invalid_payload'], 422);
        }
        $payload['type'] = $bodyType !== '' ? $bodyType : $header;

        try {
            $accepted = $this->processor->accept($payload);
        } catch (\InvalidArgumentException) {
            return response()->json(['error' => 'invalid_payload'], 422);
        }

        return response()->json($accepted);
    }
}
