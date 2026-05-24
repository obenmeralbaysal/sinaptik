<?php

namespace App\Services\X;

use App\Contracts\SocialProvider;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class XClient implements SocialProvider
{
    public function __construct(private readonly XOAuthService $oauth)
    {
    }

    public function postContent(SocialAccount $account, string $content, array $mediaIds = []): array
    {
        $account = $this->oauth->refreshIfExpiring($account);

        $payload = ['text' => $content];
        if (! empty($mediaIds)) {
            $payload['media'] = ['media_ids' => array_values($mediaIds)];
        }

        $response = Http::withToken($account->access_token)
            ->acceptJson()
            ->asJson()
            ->post('https://api.x.com/2/tweets', $payload);

        if ($response->status() !== 201) {
            throw new RuntimeException('X post failed ('.$response->status().'): '.$response->body());
        }

        $data = $response->json('data', []);
        return [
            'id' => $data['id'] ?? null,
            'text' => $data['text'] ?? $content,
        ];
    }

    public function replyToPost(SocialAccount $account, string $providerPostId, string $content): array
    {
        $account = $this->oauth->refreshIfExpiring($account);

        $response = Http::withToken($account->access_token)
            ->acceptJson()
            ->asJson()
            ->post('https://api.x.com/2/tweets', [
                'text' => $content,
                'reply' => ['in_reply_to_tweet_id' => $providerPostId],
            ]);

        if ($response->status() !== 201) {
            throw new RuntimeException('X reply failed ('.$response->status().'): '.$response->body());
        }

        $data = $response->json('data', []);
        return [
            'id' => $data['id'] ?? null,
            'text' => $data['text'] ?? $content,
        ];
    }

    public function fetchUserTimeline(SocialAccount $account, string $username, int $limit = 5): array
    {
        $account = $this->oauth->refreshIfExpiring($account);

        $userId = $this->resolveUserId($account, $username);
        if (! $userId) {
            return [];
        }

        $apiLimit = max(5, min(100, $limit));

        $response = Http::withToken($account->access_token)
            ->acceptJson()
            ->get("https://api.x.com/2/users/{$userId}/tweets", [
                'max_results' => $apiLimit,
                'tweet.fields' => 'created_at',
                'exclude' => 'retweets,replies',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('X timeline fetch failed ('.$response->status().'): '.$response->body());
        }

        $tweets = $response->json('data', []) ?? [];
        return array_slice($tweets, 0, $limit);
    }

    public function getMe(SocialAccount $account): array
    {
        $account = $this->oauth->refreshIfExpiring($account);

        $response = Http::withToken($account->access_token)
            ->acceptJson()
            ->get('https://api.x.com/2/users/me', ['user.fields' => 'profile_image_url,username,name']);

        return $response->json('data', []) ?? [];
    }

    private function resolveUserId(SocialAccount $account, string $username): ?string
    {
        $username = ltrim($username, '@');
        $cacheKey = 'x_user_id:'.strtolower($username);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($account, $username) {
            $response = Http::withToken($account->access_token)
                ->acceptJson()
                ->get("https://api.x.com/2/users/by/username/{$username}");

            if (! $response->successful()) {
                return null;
            }

            return $response->json('data.id');
        });
    }
}
