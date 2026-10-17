<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SinappHubGateway implements BillingGateway
{
    public function createCheckoutSession(Workspace $workspace, Plan $plan, string $period, User $customer, string $successUrl, string $cancelUrl): array
    {
        $body = json_encode([
            'external_reference' => (string) $workspace->id,
            'plan_code' => $plan->code,
            'period' => $period,
            'customer' => [
                'email' => $customer->email,
                'name' => $customer->name,
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ], JSON_UNESCAPED_SLASHES);

        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, (string) config('billing.hub.secret'));
        $url = rtrim((string) config('billing.hub.url'), '/').config('billing.hub.checkout_path');

        try {
            $response = Http::withHeaders([
                config('billing.hub.key_header') => (string) config('billing.hub.integration_key'),
                config('billing.hub.timestamp_header') => $timestamp,
                config('billing.hub.signature_header') => $signature,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->withBody($body, 'application/json')->post($url)->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Ödeme merkezi checkout oturumu açamadı.', previous: $exception);
        }

        $json = $response->json();

        return [
            'session_id' => (string) ($json['id'] ?? $json['session_id'] ?? ''),
            'redirect_url' => (string) ($json['redirect_url'] ?? $json['url'] ?? ''),
        ];
    }
}
