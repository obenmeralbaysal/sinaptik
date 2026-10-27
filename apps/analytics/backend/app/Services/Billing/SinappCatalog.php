<?php

namespace App\Services\Billing;

use App\Billing\SinappPlanCatalog;
use App\Models\Plan;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SinappCatalog
{
    /**
     * @return array{product: string, currency: string, vat_rate: int, updated_at: string, plans: list<array<string, mixed>>}
     */
    public function payload(): array
    {
        $plans = $this->storedPlans();
        if ($plans === null) {
            return SinappPlanCatalog::payload();
        }

        $rows = [];
        foreach ($plans as $plan) {
            $rows[] = [
                'code' => $plan->code,
                'name' => $plan->name,
                'interval' => $plan->interval,
                'price_net' => round(((int) $plan->price_net_cents) / 100, 2),
                'price_gross' => round(((int) $plan->price_gross_cents) / 100, 2),
                'features' => $this->features($plan),
                'highlighted' => (bool) $plan->highlighted,
                'active' => (bool) $plan->is_active,
            ];
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
        if (str_ends_with($planCode, '-'.$interval)) {
            return $planCode;
        }

        return config('billing.sinapp.product').'-'.$planCode.'-'.$interval;
    }

    /**
     * @return array{plan: string, interval: string}|null
     */
    public function parse(string $code): ?array
    {
        $plan = Plan::query()->where('code', $code)->first();
        if ($plan) {
            $interval = in_array($plan->interval, ['monthly', 'yearly'], true) ? $plan->interval : 'monthly';

            return ['plan' => $plan->code, 'interval' => $interval];
        }

        $product = preg_quote((string) config('billing.sinapp.product'), '/');
        if (! preg_match('/^'.$product.'-([a-z0-9]+)-(monthly|yearly)$/', $code, $matches)) {
            return null;
        }

        return ['plan' => $matches[1], 'interval' => $matches[2]];
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

        $limit = $plan->site_limit;
        $lines[] = $limit === null ? 'Sınırsız site' : ((int) $limit).' site';

        return array_values(array_unique($lines));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Plan>|null
     */
    private function storedPlans()
    {
        try {
            if (! Schema::hasTable('plans') || ! Schema::hasColumn('plans', 'is_public')) {
                return null;
            }

            $plans = Plan::query()->where('is_public', true)->orderBy('sort_order')->orderBy('id')->get();

            return $plans->isEmpty() ? null : $plans;
        } catch (Throwable) {
            return null;
        }
    }
}
