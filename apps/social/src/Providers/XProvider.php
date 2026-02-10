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
        // Determine the token: User's OAuth 2.0 Access Token or App's Bearer Token
        $token = $this->credentials['access_token'] ?? \App\Core\Config::get('X_BEARER_TOKEN');

        if (empty($token)) {
            error_log("XProvider: No valid token (User or App) found for searchRecentPosts.");
            return [];
        }

        // 1. Get User ID
        $userUrl = "https://api.twitter.com/2/users/by/username/" . urlencode($username);

        $ch = curl_init($userUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json"
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!isset($response['data']['id'])) {
            echo "\nDEBUG: User Lookup Response:\n";
            var_dump($response);
            error_log("XProvider: User lookup failed for $username. Response: " . json_encode($response));
            return [];
        }

        $userId = $response['data']['id'];

        // 2. Get Tweets
        $tweetsUrl = "https://api.twitter.com/2/users/$userId/tweets?max_results=$limit&tweet.fields=created_at&exclude=retweets,replies";

        $ch = curl_init($tweetsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json"
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response['data'] ?? [];
    }

    private function generateOAuthHeader(string $method, string $url, array $queryParams = []): string
    {
        $apiKey = $this->credentials['api_key'] ?? \App\Core\Config::get('X_API_KEY');
        $apiSecret = $this->credentials['api_secret'] ?? \App\Core\Config::get('X_API_KEY_SECRET') ?? \App\Core\Config::get('X_API_SECRET'); // Handle varied naming
        $accessToken = $this->credentials['access_token'] ?? \App\Core\Config::get('X_ACCESS_TOKEN');
        $accessTokenSecret = $this->credentials['access_token_secret'] ?? \App\Core\Config::get('X_ACCESS_TOKEN_SECRET');

        // Ensure we have strings, not nulls
        $apiKey = (string) $apiKey;
        $apiSecret = (string) $apiSecret;
        $accessToken = (string) $accessToken;
        $accessTokenSecret = (string) $accessTokenSecret;

        $nonce = bin2hex(random_bytes(16));
        $timestamp = time();

        $oauthParams = [
            'oauth_consumer_key' => $apiKey,
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_token' => $accessToken,
            'oauth_version' => '1.0'
        ];

        // Combine OAuth params and Query params for signature
        $signatureParams = array_merge($oauthParams, $queryParams);
        ksort($signatureParams);

        $baseStringParams = [];
        foreach ($signatureParams as $k => $v) {
            $baseStringParams[] = urlencode($k) . '=' . urlencode($v);
        }

        $baseString = strtoupper($method) . "&" . urlencode($url) . "&" . urlencode(implode('&', $baseStringParams));
        $signingKey = urlencode($apiSecret) . "&" . urlencode($accessTokenSecret);
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        $oauthParams['oauth_signature'] = $signature;

        $headerParts = [];
        foreach ($oauthParams as $k => $v) {
            $headerParts[] = urlencode($k) . '="' . urlencode($v) . '"';
        }

        return "OAuth " . implode(', ', $headerParts);
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
