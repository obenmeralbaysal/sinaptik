<?php

namespace App\Providers;

class XProvider implements ProviderInterface
{
    private array $credentials;

    public function authenticate(array $credentials): bool
    {
        $this->credentials = $credentials;
        return true;
    }

    private function getUserId(string $username, string $token): ?string
    {
        $cacheFile = __DIR__ . '/../../storage/cache/x_user_ids.json';
        $cache = [];

        if (file_exists($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true) ?? [];
        }

        // Check Cache
        if (isset($cache[$username])) {
            echo "<li>User ID loaded from Cache: " . $cache[$username] . "</li>";
            return $cache[$username];
        }

        // Fetch from API
        $userUrl = "https://api.twitter.com/2/users/by/username/" . urlencode($username);
        $ch = curl_init($userUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $token, // Use Access Token here too
            "Content-Type: application/json"
        ]);
        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($responseRaw, true);

        // Debug Output
        echo "<li>User Lookup API Status: $httpCode</li>";
        if ($httpCode !== 200) {
            echo "<h3>User Lookup Error (Usually 429 Rate Limit)</h3><pre>" . htmlspecialchars($responseRaw) . "</pre>";
        }

        if (isset($response['data']['id'])) {
            $userId = $response['data']['id'];

            // Save to Cache
            $cache[$username] = $userId;

            if (!is_dir(dirname($cacheFile))) {
                mkdir(dirname($cacheFile), 0777, true);
            }

            file_put_contents($cacheFile, json_encode($cache));
            echo "<li>User ID fetched & cached: $userId</li>";
            return $userId;
        }

        error_log("XProvider: User lookup failed for $username. Response: " . $responseRaw);
        return null;
    }

    private function refreshAccessTokenIfExpired(): void
    {
        if (!isset($this->credentials['expires_at']) || !isset($this->credentials['refresh_token'])) {
            return;
        }

        if (strtotime($this->credentials['expires_at']) < time()) {
            error_log("XProvider: Token expired (expires_at: " . $this->credentials['expires_at'] . "). Refreshing...");

            try {
                $xService = new \App\Services\XService();
                $newTokens = $xService->refreshTokens($this->credentials['refresh_token']);

                if (isset($newTokens['access_token'])) {
                    $expiresIn = $newTokens['expires_in'] ?? 7200;
                    $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

                    // Update Database
                    if (isset($this->credentials['id'])) {
                        \App\Models\Account::updateTokens(
                            $this->credentials['id'],
                            $newTokens['access_token'],
                            $newTokens['refresh_token'] ?? null,
                            $expiresAt
                        );
                        error_log("XProvider: Token refreshed and DB updated.");
                    }

                    // Update Local State
                    $this->credentials['access_token'] = $newTokens['access_token'];
                    $this->credentials['expires_at'] = $expiresAt;
                    if (isset($newTokens['refresh_token'])) {
                        $this->credentials['refresh_token'] = $newTokens['refresh_token'];
                    }
                } else {
                    error_log("XProvider: Failed to refresh token. Response: " . json_encode($newTokens));
                }
            } catch (\Exception $e) {
                error_log("XProvider: Exception refreshing token: " . $e->getMessage());
            }
        }
    }

    public function post(string $content): bool
    {
        $this->refreshAccessTokenIfExpired();

        $apiUrl = "https://api.twitter.com/2/tweets";
        $accessToken = $this->credentials['access_token'] ?? null;

        if (!$accessToken) {
            error_log("XProvider::post - Missing access token");
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['text' => $content]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 201) {
            echo "<h3>API Error (HTTP $httpCode)</h3>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
            error_log("XProvider::post FAILED. HTTP: $httpCode. Response: " . $response);
            error_log("Curl Error: " . curl_error($ch));
        }

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
        // 1. Refresh & Get Access Token (User Context)
        $this->refreshAccessTokenIfExpired();
        $accessToken = $this->credentials['access_token'] ?? null;

        if (empty($accessToken)) {
            echo "<h3 style='color:red'>Access Token missing for user!</h3>";
            return [];
        }

        echo "<li>Using Access Token: " . substr($accessToken, 0, 10) . "...</li>";

        // 2. Get User ID (Uses Cache first, then API with Access Token)
        // Pass Access Token to use User Context limits for lookup too
        $userId = $this->getUserId($username, $accessToken);

        if (!$userId) {
            echo "<h3 style='color:red'>Could not resolve User ID for $username</h3>";
            return [];
        }

        // 3. Fetch User Timeline (users/:id/tweets)
        // Rate Limit: 1500 requests / 15 mins (User Context) vs 180 for Search
        $tweetsUrl = "https://api.twitter.com/2/users/$userId/tweets?max_results=$limit&tweet.fields=created_at&exclude=retweets,replies";

        $ch = curl_init($tweetsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $accessToken,
            "Content-Type: application/json"
        ]);
        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Echo debug info
        echo "<li>Timeline API Status: $httpCode</li>";

        if ($httpCode !== 200) {
            echo "<h3>Timeline API Error</h3><pre>" . htmlspecialchars($responseRaw) . "</pre>";
            error_log("XProvider::searchRecentPosts FAILED. HTTP: $httpCode. Response: " . $responseRaw);
            return [];
        }

        $data = json_decode($responseRaw, true);
        return $data['data'] ?? [];
    }

    public function reply(string $postId, string $content): bool
    {
        $this->refreshAccessTokenIfExpired();

        $apiUrl = "https://api.twitter.com/2/tweets";
        $accessToken = $this->credentials['access_token'] ?? null;

        if (!$accessToken) {
            error_log("XProvider::reply - Missing access token");
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
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
