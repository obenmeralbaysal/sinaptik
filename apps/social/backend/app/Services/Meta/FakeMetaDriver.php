<?php

namespace App\Services\Meta;

use App\Models\Post;
use App\Models\SocialAccount;

class FakeMetaDriver implements MetaDriver
{
    /** @var list<string> */
    public static array $revokedTokens = [];

    public function loginUrl(string $state, string $redirectUri): string
    {
        $version = config('services.meta.graph_version');

        return 'https://www.facebook.com/'.$version.'/dialog/oauth?client_id='.urlencode((string) config('services.meta.app_id'))
            .'&redirect_uri='.urlencode($redirectUri)
            .'&state='.urlencode($state)
            .'&config_id='.urlencode((string) config('services.meta.config_id'))
            .'&response_type=code';
    }

    public function exchangeCode(string $code, string $redirectUri): array
    {
        return ['access_token' => 'fake-user-'.$code, 'expires_in' => 3600];
    }

    public function exchangeLongLived(string $token): array
    {
        return ['access_token' => 'll-'.$token, 'expires_in' => 60 * 24 * 3600];
    }

    public function debugToken(string $token): array
    {
        $valid = ! str_contains($token, 'invalid');

        return [
            'is_valid' => $valid,
            'expires_at' => $valid ? now()->addDays(50)->getTimestamp() : null,
            'scopes' => ['pages_show_list', 'pages_manage_posts', 'instagram_content_publish', 'ads_read', 'ads_management'],
            'user_id' => '10001',
            'error' => $valid ? null : 'token invalid',
        ];
    }

    public function listPages(string $userToken): array
    {
        return [[
            'id' => '111',
            'name' => 'Sinapp Sayfa',
            'access_token' => 'page-token-111',
            'instagram_business_account' => ['id' => '222', 'username' => 'sinapp', 'name' => 'Sinapp IG'],
        ]];
    }

    public function listAdAccounts(string $userToken): array
    {
        return [[
            'id' => 'act_333',
            'name' => 'Sinapp Ads',
            'currency' => 'TRY',
            'account_status' => 1,
        ]];
    }

    public function publish(SocialAccount $account, Post $post, array $mediaUrls): array
    {
        return ['id' => 'ext_'.$account->platform.'_'.$post->id];
    }

    public function accountInsights(SocialAccount $account): array
    {
        return ['impressions' => 120, 'reach' => 80, 'engagement' => 14];
    }

    public function postInsights(SocialAccount $account, string $externalId): array
    {
        return ['impressions' => 40, 'reach' => 30, 'engagement' => 5, 'external_id' => $externalId];
    }

    public function syncAds(string $token, string $adAccountId): array
    {
        return [
            'campaigns' => [[
                'id' => 'cmp_1', 'name' => 'Lansman', 'status' => 'ACTIVE', 'objective' => 'OUTCOME_TRAFFIC', 'daily_budget' => 10000,
            ]],
            'adsets' => [[
                'id' => 'set_1', 'campaign_id' => 'cmp_1', 'name' => 'TR 25-44', 'status' => 'ACTIVE', 'daily_budget' => 5000,
            ]],
            'ads' => [[
                'id' => 'ad_1', 'adset_id' => 'set_1', 'name' => 'Görsel A', 'status' => 'ACTIVE',
            ]],
            'insights' => [[
                'level' => 'adset', 'external_id' => 'set_1', 'metrics' => ['spend' => 12.5, 'clicks' => 40, 'impressions' => 1000],
            ]],
        ];
    }

    public function updateBudget(string $token, string $externalId, int $dailyBudgetMinor): void {}

    public function updateStatus(string $token, string $externalId, string $status): void {}

    public function revokePermissions(string $token): void
    {
        self::$revokedTokens[] = $token;
    }
}
