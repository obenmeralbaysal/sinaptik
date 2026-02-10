<?php

namespace App\Providers;

class XProvider implements ProviderInterface
{
    private array $credentials;

    public function authenticate(array $credentials): bool
    {
        $this->credentials = $credentials;
        // Logic for OAuth validation would go here
        return true;
    }

    public function post(string $content): bool
    {
        $apiUrl = "https://api.twitter.com/2/tweets";

        $apiKey = $this->credentials['api_key'];
        $apiSecret = $this->credentials['api_secret'];
        $accessToken = $this->credentials['access_token'];
        $accessTokenSecret = $this->credentials['access_token_secret'];

        $nonce = bin2hex(random_bytes(16));
        $timestamp = time();

        $params = [
            'oauth_consumer_key' => $apiKey,
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_token' => $accessToken,
            'oauth_version' => '1.0'
        ];

        ksort($params);
        $baseString = "POST&" . urlencode($apiUrl) . "&" . urlencode(http_build_query($params));
        $signingKey = urlencode($apiSecret) . "&" . urlencode($accessTokenSecret);
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
        $params['oauth_signature'] = $signature;

        $authHeader = "OAuth ";
        $headerParts = [];
        foreach ($params as $k => $v) {
            $headerParts[] = urlencode($k) . '="' . urlencode($v) . '"';
        }
        $authHeader .= implode(', ', $headerParts);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: $authHeader",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['text' => $content]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 201;
    }

    public function getUserLastTweet(string $username): ?array
    {
        $tweets = $this->searchRecentPosts($username, 1);
        return $tweets[0] ?? null;
    }

    public function searchRecentPosts(string $username, int $limit = 5): array
    {
        // First get user ID by username
        $userUrl = "https://api.twitter.com/2/users/by/username/" . urlencode($username);
        $ch = curl_init($userUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->credentials['bearer_token']
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!isset($response['data']['id']))
            return [];
        $userId = $response['data']['id'];

        // Then get latest tweets
        $tweetsUrl = "https://api.twitter.com/2/users/$userId/tweets?max_results=$limit&tweet.fields=created_at";
        $ch = curl_init($tweetsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->credentials['bearer_token']
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response['data'] ?? [];
    }

    public function reply(string $postId, string $content): bool
    {
        $apiUrl = "https://api.twitter.com/2/tweets";

        $apiKey = $this->credentials['api_key'];
        $apiSecret = $this->credentials['api_secret'];
        $accessToken = $this->credentials['access_token'];
        $accessTokenSecret = $this->credentials['access_token_secret'];

        $nonce = bin2hex(random_bytes(16));
        $timestamp = time();

        $params = [
            'oauth_consumer_key' => $apiKey,
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_token' => $accessToken,
            'oauth_version' => '1.0'
        ];

        ksort($params);
        $baseString = "POST&" . urlencode($apiUrl) . "&" . urlencode(http_build_query($params));
        $signingKey = urlencode($apiSecret) . "&" . urlencode($accessTokenSecret);
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
        $params['oauth_signature'] = $signature;

        $authHeader = "OAuth ";
        $headerParts = [];
        foreach ($params as $k => $v) {
            $headerParts[] = urlencode($k) . '="' . urlencode($v) . '"';
        }
        $authHeader .= implode(', ', $headerParts);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: $authHeader",
            "Content-Type: application/json"
        ]);

        $payload = [
            'text' => $content,
            'reply' => ['in_reply_to_tweet_id' => $postId]
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 201;
    }
}
