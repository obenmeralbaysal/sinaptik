<?php

namespace App\Providers;

interface ProviderInterface
{
    public function authenticate(array $credentials): bool;
    public function post(string $content): bool;
    public function searchRecentPosts(string $username, int $limit = 5): array;
    public function reply(string $postId, string $content): bool;
}
