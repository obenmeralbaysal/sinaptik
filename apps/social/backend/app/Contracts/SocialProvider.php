<?php

namespace App\Contracts;

use App\Models\SocialAccount;

interface SocialProvider
{
    public function postContent(SocialAccount $account, string $content, array $mediaIds = []): array;

    public function replyToPost(SocialAccount $account, string $providerPostId, string $content): array;

    public function fetchUserTimeline(SocialAccount $account, string $username, int $limit = 5): array;
}
