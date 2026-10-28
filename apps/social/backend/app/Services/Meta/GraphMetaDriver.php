<?php

namespace App\Services\Meta;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GraphMetaDriver implements MetaDriver
{
    public function loginUrl(string $state, string $redirectUri): string
    {
        $version = $this->version();

        return "https://www.facebook.com/{$version}/dialog/oauth?".http_build_query([
            'client_id' => config('services.meta.app_id'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'config_id' => config('services.meta.config_id'),
            'response_type' => 'code',
            'override_default_response_type' => 'true',
        ]);
    }

    public function exchangeCode(string $code, string $redirectUri): array
    {
        return $this->get('/oauth/access_token', [
            'client_id' => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);
    }

    public function exchangeLongLived(string $token): array
    {
        return $this->get('/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'fb_exchange_token' => $token,
        ]);
    }

    public function debugToken(string $token): array
    {
        $appToken = config('services.meta.app_id').'|'.config('services.meta.app_secret');
        $json = $this->get('/debug_token', [
            'input_token' => $token,
            'access_token' => $appToken,
        ]);
        $data = (array) ($json['data'] ?? []);

        return [
            'is_valid' => (bool) ($data['is_valid'] ?? false),
            'expires_at' => $data['expires_at'] ?? null,
            'scopes' => $data['scopes'] ?? [],
            'user_id' => isset($data['user_id']) ? (string) $data['user_id'] : null,
            'error' => $data['error']['message'] ?? null,
        ];
    }

    public function listPages(string $userToken): array
    {
        $json = $this->get('/me/accounts', [
            'fields' => 'id,name,access_token,instagram_business_account{id,username,name}',
            'access_token' => $userToken,
        ]);

        return array_values((array) ($json['data'] ?? []));
    }

    public function listAdAccounts(string $userToken): array
    {
        $json = $this->get('/me/adaccounts', [
            'fields' => 'id,name,currency,account_status',
            'access_token' => $userToken,
        ]);

        return array_values((array) ($json['data'] ?? []));
    }

    public function publish(SocialAccount $account, Post $post, array $mediaUrls): array
    {
        $token = (string) $account->access_token;
        if ($account->platform === 'instagram') {
            return $this->publishInstagram($account->external_id, $token, $post, $mediaUrls);
        }

        return $this->publishFacebook($account->external_id, $token, $post, $mediaUrls);
    }

    public function accountInsights(SocialAccount $account): array
    {
        $metric = $account->platform === 'instagram'
            ? 'impressions,reach,profile_views'
            : 'page_impressions,page_post_engagements';
        $json = $this->get('/'.$account->external_id.'/insights', [
            'metric' => $metric,
            'period' => 'day',
            'access_token' => $account->access_token,
        ]);

        return ['raw' => $json['data'] ?? []];
    }

    public function postInsights(SocialAccount $account, string $externalId): array
    {
        $json = $this->get('/'.$externalId.'/insights', [
            'metric' => 'impressions,reach,engagement',
            'access_token' => $account->access_token,
        ]);

        return ['raw' => $json['data'] ?? []];
    }

    public function syncAds(string $token, string $adAccountId): array
    {
        $id = str_starts_with($adAccountId, 'act_') ? $adAccountId : 'act_'.$adAccountId;
        $campaigns = $this->get("/{$id}/campaigns", ['fields' => 'id,name,status,objective,daily_budget', 'access_token' => $token]);
        $adsets = $this->get("/{$id}/adsets", ['fields' => 'id,name,status,daily_budget,campaign_id', 'access_token' => $token]);
        $ads = $this->get("/{$id}/ads", ['fields' => 'id,name,status,adset_id', 'access_token' => $token]);
        $insights = $this->get("/{$id}/insights", ['level' => 'adset', 'date_preset' => 'yesterday', 'fields' => 'adset_id,spend,clicks,impressions', 'access_token' => $token]);

        return [
            'campaigns' => $campaigns['data'] ?? [],
            'adsets' => $adsets['data'] ?? [],
            'ads' => $ads['data'] ?? [],
            'insights' => $insights['data'] ?? [],
        ];
    }

    public function updateBudget(string $token, string $externalId, int $dailyBudgetMinor): void
    {
        $this->post('/'.$externalId, [
            'daily_budget' => $dailyBudgetMinor,
            'access_token' => $token,
        ]);
    }

    public function revokePermissions(string $token): void
    {
        $response = Http::timeout(30)->withQueryParameters([
            'access_token' => $token,
        ])->delete($this->base().'/me/permissions');

        if ($response->failed()) {
            Log::warning('meta.graph_failed', ['path' => '/me/permissions', 'status' => $response->status()]);
            throw new RuntimeException('Meta isteği başarısız: '.$response->status());
        }
    }

    public function updateStatus(string $token, string $externalId, string $status): void
    {
        $this->post('/'.$externalId, [
            'status' => $status,
            'access_token' => $token,
        ]);
    }

    public function listComments(SocialAccount $account, string $externalPostId): array
    {
        if ($account->platform === 'instagram') {
            $json = $this->get('/'.$externalPostId.'/comments', [
                'fields' => 'id,text,username,timestamp,hidden,user,replies{id,text,username,timestamp,hidden,user}',
                'limit' => 50,
                'access_token' => $account->access_token,
            ]);

            return $this->flattenInstagramComments((array) ($json['data'] ?? []));
        }

        $json = $this->get('/'.$externalPostId.'/comments', [
            'filter' => 'stream',
            'fields' => 'id,message,from,created_time,parent,is_hidden',
            'limit' => 50,
            'access_token' => $account->access_token,
        ]);

        return array_map(
            fn (array $item): array => $this->normalizeFacebookComment($item),
            array_values(array_filter((array) ($json['data'] ?? []), 'is_array')),
        );
    }

    public function replyToComment(SocialAccount $account, string $commentExternalId, string $message): array
    {
        $path = $account->platform === 'instagram'
            ? '/'.$commentExternalId.'/replies'
            : '/'.$commentExternalId.'/comments';
        $json = $this->post($path, [
            'message' => $message,
            'access_token' => $account->access_token,
        ]);
        $id = (string) ($json['id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('Meta yorum yanıtı kimliği dönmedi.');
        }

        return ['external_id' => $id, 'body' => $message];
    }

    public function hideComment(SocialAccount $account, string $commentExternalId, bool $hide = true): void
    {
        $flag = $hide ? 'true' : 'false';
        $body = $account->platform === 'instagram'
            ? ['hide' => $flag, 'access_token' => $account->access_token]
            : ['is_hidden' => $flag, 'access_token' => $account->access_token];
        $this->post('/'.$commentExternalId, $body);
    }

    public function deleteComment(SocialAccount $account, string $commentExternalId): void
    {
        $this->delete('/'.$commentExternalId, [
            'access_token' => $account->access_token,
        ]);
    }

    private function publishFacebook(string $pageId, string $token, Post $post, array $mediaUrls): array
    {
        if ($post->post_type === 'video' || $post->post_type === 'reel') {
            return $this->post("/{$pageId}/videos", [
                'description' => (string) $post->body,
                'file_url' => $mediaUrls[0] ?? null,
                'access_token' => $token,
            ]);
        }

        if ($mediaUrls !== []) {
            return $this->post("/{$pageId}/photos", [
                'caption' => (string) $post->body,
                'url' => $mediaUrls[0],
                'access_token' => $token,
            ]);
        }

        return $this->post("/{$pageId}/feed", [
            'message' => (string) $post->body,
            'access_token' => $token,
        ]);
    }

    private function publishInstagram(string $igId, string $token, Post $post, array $mediaUrls): array
    {
        $mediaType = match ($post->post_type) {
            'reel' => 'REELS',
            'story' => 'STORIES',
            'video' => 'REELS',
            'carousel' => 'CAROUSEL',
            default => null,
        };

        $params = [
            'caption' => (string) $post->body,
            'access_token' => $token,
        ];
        if ($mediaType) {
            $params['media_type'] = $mediaType;
        }
        if ($post->post_type === 'carousel') {
            $params['children'] = implode(',', $mediaUrls);
        } elseif (in_array($post->post_type, ['video', 'reel'], true)) {
            $params['video_url'] = $mediaUrls[0] ?? null;
        } else {
            $params['image_url'] = $mediaUrls[0] ?? null;
        }

        $container = $this->post("/{$igId}/media", $params);
        $creationId = $container['id'] ?? null;
        if (! $creationId) {
            throw new RuntimeException('Instagram medya kapsayıcısı oluşmadı.');
        }

        return $this->post("/{$igId}/media_publish", [
            'creation_id' => $creationId,
            'access_token' => $token,
        ]);
    }

    /** @param list<array<string, mixed>> $items */
    private function flattenInstagramComments(array $items, ?string $parentId = null): array
    {
        $rows = [];
        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['id'])) {
                continue;
            }
            $user = (array) ($item['user'] ?? []);
            $rows[] = [
                'external_id' => (string) $item['id'],
                'parent_external_id' => $parentId,
                'author_external_id' => isset($user['id']) ? (string) $user['id'] : null,
                'author_username' => isset($item['username']) ? (string) $item['username'] : null,
                'body' => isset($item['text']) ? (string) $item['text'] : null,
                'posted_at' => isset($item['timestamp']) ? (string) $item['timestamp'] : null,
                'hidden' => (bool) ($item['hidden'] ?? false),
                'raw' => $item,
            ];
            $replies = (array) ($item['replies']['data'] ?? []);
            if ($replies !== []) {
                $rows = array_merge($rows, $this->flattenInstagramComments($replies, (string) $item['id']));
            }
        }

        return $rows;
    }

    /** @param array<string, mixed> $item */
    private function normalizeFacebookComment(array $item): array
    {
        $from = (array) ($item['from'] ?? []);
        $parent = (array) ($item['parent'] ?? []);

        return [
            'external_id' => (string) ($item['id'] ?? ''),
            'parent_external_id' => isset($parent['id']) ? (string) $parent['id'] : null,
            'author_external_id' => isset($from['id']) ? (string) $from['id'] : null,
            'author_username' => isset($from['name']) ? (string) $from['name'] : null,
            'body' => isset($item['message']) ? (string) $item['message'] : null,
            'posted_at' => isset($item['created_time']) ? (string) $item['created_time'] : null,
            'hidden' => (bool) ($item['is_hidden'] ?? false),
            'raw' => $item,
        ];
    }

    private function get(string $path, array $query): array
    {
        $response = Http::timeout(30)->get($this->base().$path, $query);
        if ($response->failed()) {
            Log::warning('meta.graph_failed', ['path' => $path, 'status' => $response->status()]);
            throw new RuntimeException('Meta isteği başarısız: '.$response->status());
        }

        return $response->json() ?? [];
    }

    private function post(string $path, array $body): array
    {
        $response = Http::timeout(60)->asForm()->post($this->base().$path, $body);
        if ($response->failed()) {
            Log::warning('meta.graph_failed', ['path' => $path, 'status' => $response->status()]);
            throw new RuntimeException('Meta isteği başarısız: '.$response->status());
        }

        return $response->json() ?? [];
    }

    private function delete(string $path, array $query): void
    {
        $response = Http::timeout(30)->withQueryParameters($query)->delete($this->base().$path);
        if ($response->failed()) {
            Log::warning('meta.graph_failed', ['path' => $path, 'status' => $response->status()]);
            throw new RuntimeException('Meta isteği başarısız: '.$response->status());
        }
    }

    private function base(): string
    {
        return 'https://graph.facebook.com/'.$this->version();
    }

    private function version(): string
    {
        return (string) config('services.meta.graph_version', 'v23.0');
    }
}
