<?php

namespace App\Services\Meta;

use App\Models\Post;
use App\Models\SocialAccount;

interface MetaDriver
{
    public function loginUrl(string $state, string $redirectUri): string;

    /** @return array{access_token: string, expires_in?: int} */
    public function exchangeCode(string $code, string $redirectUri): array;

    /** @return array{access_token: string, expires_in?: int} */
    public function exchangeLongLived(string $token): array;

    /** @return array{is_valid: bool, expires_at?: int|null, scopes?: array, user_id?: string|null, error?: string|null} */
    public function debugToken(string $token): array;

    /** @return list<array<string, mixed>> */
    public function listPages(string $userToken): array;

    /** @return list<array<string, mixed>> */
    public function listAdAccounts(string $userToken): array;

    /** @return array<string, mixed> */
    public function publish(SocialAccount $account, Post $post, array $mediaUrls): array;

    /** @return array<string, mixed> */
    public function accountInsights(SocialAccount $account): array;

    /** @return array<string, mixed> */
    public function postInsights(SocialAccount $account, string $externalId): array;

    /** @return array{campaigns: list<array>, adsets: list<array>, ads: list<array>, insights: list<array>} */
    public function syncAds(string $token, string $adAccountId): array;

    public function updateBudget(string $token, string $externalId, int $dailyBudgetMinor): void;

    public function updateStatus(string $token, string $externalId, string $status): void;
}
