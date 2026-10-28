<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_values(config('sinapp.plans', [])) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            Plan::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'interval' => $row['interval'],
                    'price_net_cents' => self::cents((string) $row['price_net']),
                    'price_gross_cents' => self::cents((string) $row['price_gross']),
                    'currency' => (string) config('sinapp.currency', 'TRY'),
                    'site_limit' => $row['site_limit'] ?? null,
                    'features' => $row['features'] ?? [],
                    'highlighted' => (bool) ($row['highlighted'] ?? false),
                    'is_active' => (bool) ($row['active'] ?? true),
                    'is_public' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }

    private static function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole) * 100 + (int) substr(str_pad($fraction, 2, '0'), 0, 2);
    }
}
