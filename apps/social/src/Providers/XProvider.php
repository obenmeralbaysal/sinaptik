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

    public function searchRecentPosts(string $username, int $limit = 10): array
    {
        // Refresh token if needed (since we are now using User Context)
        $this->refreshAccessTokenIfExpired();

        $accessToken = $this->credentials['access_token'] ?? null;

        if (empty($accessToken)) {
            echo "<h3 style='color:red'>Access Token missing for user!</h3>";
            error_log("XProvider: Access Token missing in credentials.");
            return [];
        }

        // Use 'tweets/search/recent' to fetch by username directly
        // Query: from:username -is:retweet -is:reply
        $query = "from:$username -is:retweet -is:reply";
        $apiUrl = "https://api.twitter.com/2/tweets/search/recent?query=" . urlencode($query) . "&max_results=$limit&tweet.fields=created_at";

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $accessToken,
            "Content-Type: application/json"
        ]);

        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Echo debug info
        echo "<li>Search API Status: $httpCode</li>";

        if ($httpCode !== 200) {
            echo "<h3>Search API Error</h3><pre>" . htmlspecialchars($responseRaw) . "</pre>";
            error_log("XProvider::searchRecentPosts FAILED. HTTP: $httpCode. Response: " . $responseRaw);
            return [];
        }

        $data = json_decode($responseRaw, true);

        if (!isset($data['data'])) {
            echo "<li>No tweets found in the last 7 days (Search API restriction).</li>";
        }

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
