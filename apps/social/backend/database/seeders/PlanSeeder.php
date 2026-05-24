<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'price_cents' => 19900,
                'currency' => 'TRY',
                'x_account_limit' => 1,
                'daily_post_limit' => 3,
                'daily_reply_limit' => 10,
                'features' => ['ai_reply' => true, 'basic_support' => true],
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'price_cents' => 49900,
                'currency' => 'TRY',
                'x_account_limit' => 5,
                'daily_post_limit' => 10,
                'daily_reply_limit' => 50,
                'features' => ['ai_reply' => true, 'scheduling' => true, 'advanced_support' => true],
            ],
            [
                'slug' => 'agency',
                'name' => 'Agency',
                'price_cents' => 99900,
                'currency' => 'TRY',
                'x_account_limit' => 20,
                'daily_post_limit' => 50,
                'daily_reply_limit' => 200,
                'features' => ['ai_reply' => true, 'team_access' => true, 'priority_support' => true],
            ],
        ];

        foreach ($plans as $p) {
            Plan::updateOrCreate(['slug' => $p['slug']], $p);
        }
    }
}
