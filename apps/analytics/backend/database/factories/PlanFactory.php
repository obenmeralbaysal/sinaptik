<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'plan-'.fake()->unique()->lexify('????'),
            'name' => 'Başlangıç',
            'interval' => 'monthly',
            'price_net_cents' => 24900,
            'price_gross_cents' => 29880,
            'currency' => 'TRY',
            'site_limit' => 1,
            'features' => ['1 site'],
            'highlighted' => false,
            'is_active' => true,
            'is_public' => true,
            'sort_order' => 0,
        ];
    }
}
