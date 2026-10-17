<?php

namespace App\Services\Billing;

use App\Models\Plan;

class SinappCatalog
{
    public function payload(): array
    {
        $plans = Plan::query()->where('is_public', true)->orderBy('sort_order')->orderBy('id')->get();
        $rows = [];

        foreach ($plans as $plan) {
            foreach (['monthly', 'yearly'] as $interval) {
                $prices = $this->prices($plan->priceFor($interval));
                $rows[] = [
                    'code' => $this->code($plan->code, $interval),
                    'name' => $plan->name,
                    'interval' => $interval,
                    'price_net' => $prices['net'],
                    'price_gross' => $prices['gross'],
                    'features' => $this->features($plan),
                    'highlighted' => $plan->code === 'pro',
                    'active' => (bool) $plan->is_active,
                ];
            }
        }

        $updated = $plans->max('updated_at');

        return [
            'product' => (string) config('billing.sinapp.product'),
            'currency' => (string) config('billing.sinapp.currency'),
            'vat_rate' => (int) config('billing.sinapp.vat_rate'),
            'plans' => $rows,
            'updated_at' => ($updated ?? now())->toIso8601String(),
        ];
    }

    public function code(string $planCode, string $interval): string
    {
        return config('billing.sinapp.product').'-'.$planCode.'-'.$interval;
    }

    /**
     * @return array{plan: string, interval: string}|null
     */
    public function parse(string $code): ?array
    {
        $product = preg_quote((string) config('billing.sinapp.product'), '/');
        if (! preg_match('/^'.$product.'-([a-z0-9]+)-(monthly|yearly)$/', $code, $matches)) {
            return null;
        }

        return ['plan' => $matches[1], 'interval' => $matches[2]];
    }

    /**
     * @return array{net: float, gross: float}
     */
    public function prices(int $grossMinor): array
    {
        $vat = (int) config('billing.sinapp.vat_rate');
        $netMinor = (int) round($grossMinor / (1 + ($vat / 100)));

        return [
            'gross' => round($grossMinor / 100, 2),
            'net' => round($netMinor / 100, 2),
        ];
    }

    /**
     * @return list<string>
     */
    public function features(Plan $plan): array
    {
        $lines = [];
        foreach ($plan->features ?? [] as $feature) {
            if (is_string($feature) && trim($feature) !== '') {
                $lines[] = trim($feature);
            }
        }

        $limits = $plan->limits ?? [];
        $derived = [
            'social_accounts' => fn ($value) => $value === null ? 'Sınırsız sosyal hesap' : ((int) $value).' sosyal hesap',
            'posts_per_month' => fn ($value) => $value === null ? 'Sınırsız gönderi' : ((int) $value).' gönderi / ay',
            'seats' => fn ($value) => $value === null ? 'Sınırsız kullanıcı' : ((int) $value).' kullanıcı',
            'ad_accounts' => fn ($value) => (int) $value === 0 ? null : ((int) $value).' reklam hesabı',
            'automation_rules' => fn ($value) => (int) $value === 0 ? null : ((int) $value).' otomasyon kuralı',
            'ai_credits_per_month' => fn ($value) => ((int) $value).' yapay zeka kredisi / ay',
            'storage_gb' => fn ($value) => ((int) $value).' GB depolama',
        ];

        foreach ($derived as $key => $format) {
            if (! array_key_exists($key, $limits)) {
                continue;
            }
            $line = $format($limits[$key]);
            if (is_string($line) && $line !== '') {
                $lines[] = $line;
            }
        }

        if (($limits['ads_auto_apply'] ?? false) === true) {
            $lines[] = 'Tavanlı otomatik reklam';
        }

        return array_values(array_unique($lines));
    }
}
