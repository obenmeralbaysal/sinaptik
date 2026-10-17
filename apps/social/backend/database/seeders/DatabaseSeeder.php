<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'starter',
                'name' => 'Başlangıç',
                'description' => 'Tek kişilik organik paylaşım.',
                'price_monthly_minor' => 49900,
                'price_yearly_minor' => 499000,
                'sort_order' => 1,
                'limits' => [
                    'social_accounts' => 3,
                    'posts_per_month' => 60,
                    'seats' => 1,
                    'ad_accounts' => 0,
                    'automation_rules' => 0,
                    'mcp_calls_per_month' => 1000,
                    'mcp_calls_per_minute' => 30,
                    'ai_credits_per_month' => 100,
                    'storage_gb' => 5,
                    'ads_auto_apply' => false,
                ],
            ],
            [
                'code' => 'pro',
                'name' => 'Pro',
                'description' => '14 gün ücretsiz deneme. Öneri modunda reklam.',
                'price_monthly_minor' => 149900,
                'price_yearly_minor' => 1499000,
                'sort_order' => 2,
                'limits' => [
                    'social_accounts' => 10,
                    'posts_per_month' => 500,
                    'seats' => 3,
                    'ad_accounts' => 2,
                    'automation_rules' => 5,
                    'mcp_calls_per_month' => 10000,
                    'mcp_calls_per_minute' => 60,
                    'ai_credits_per_month' => 1000,
                    'storage_gb' => 25,
                    'ads_auto_apply' => false,
                ],
            ],
            [
                'code' => 'agency',
                'name' => 'Ajans',
                'description' => 'Tavanlı otomatik reklam ve yüksek kota.',
                'price_monthly_minor' => 399900,
                'price_yearly_minor' => 3999000,
                'sort_order' => 3,
                'limits' => [
                    'social_accounts' => 30,
                    'posts_per_month' => null,
                    'seats' => 10,
                    'ad_accounts' => 10,
                    'automation_rules' => 50,
                    'mcp_calls_per_month' => 50000,
                    'mcp_calls_per_minute' => 120,
                    'ai_credits_per_month' => 5000,
                    'storage_gb' => 100,
                    'ads_auto_apply' => true,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['code' => $plan['code']], $plan + [
                'currency' => 'TRY',
                'is_public' => true,
                'is_active' => true,
                'features' => [],
            ]);
        }

        $pages = [
            'gizlilik' => 'Gizlilik politikası',
            'kosullar' => 'Kullanım koşulları',
            'kvkk' => 'KVKK aydınlatma metni',
            'veri-silme' => 'Veri silme',
            'cerezler' => 'Çerez politikası',
        ];

        foreach ($pages as $slug => $title) {
            foreach (['tr' => $title, 'en' => $title.' (placeholder)'] as $locale => $localized) {
                LegalPage::query()->updateOrCreate(
                    ['slug' => $slug, 'locale' => $locale],
                    [
                        'title' => $localized,
                        'body_markdown' => "Bu sayfa yer tutucudur. Nihai {$localized} metni hukuk ekibi tarafından Filament üzerinden yayınlanacaktır.\n\nSinaptik Social, müşteri adına veri işleyen konumundadır. Kart verisi bu uygulamada tutulmaz.",
                    ],
                );
            }
        }
    }
}
