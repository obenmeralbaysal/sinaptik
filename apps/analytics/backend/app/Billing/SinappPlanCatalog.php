<?php

namespace App\Billing;

use InvalidArgumentException;

final class SinappPlanCatalog
{
    /**
     * @return array{product: string, currency: string, vat_rate: int, updated_at: string, plans: list<array<string, mixed>>}
     */
    public static function payload(?array $catalog = null): array
    {
        $catalog ??= config('sinapp');
        if (! is_array($catalog)) {
            throw new InvalidArgumentException('Sinapp plan catalog is missing.');
        }

        $vat = $catalog['vat_rate'] ?? null;
        if (! is_int($vat) || $vat < 0 || $vat > 100) {
            throw new InvalidArgumentException('Sinapp plan vat_rate must be an integer from 0 to 100.');
        }

        $rows = $catalog['plans'] ?? null;
        if (! is_array($rows) || $rows === []) {
            throw new InvalidArgumentException('Sinapp plan catalog needs a plans list.');
        }

        $plans = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException('Sinapp plan must be an object.');
            }
            $plans[] = [
                'code' => $row['code'],
                'name' => $row['name'],
                'interval' => $row['interval'],
                'price_net' => $row['price_net'],
                'price_gross' => $row['price_gross'],
                'features' => array_values($row['features'] ?? []),
                'highlighted' => (bool) $row['highlighted'],
                'active' => (bool) $row['active'],
            ];
        }

        return [
            'product' => (string) $catalog['product'],
            'currency' => (string) $catalog['currency'],
            'vat_rate' => $vat,
            'updated_at' => (string) $catalog['updated_at'],
            'plans' => $plans,
        ];
    }
}
