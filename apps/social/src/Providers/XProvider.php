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

    public function getUserId(string $username, string $token): ?string
    {
        $cacheFile = __DIR__ . '/../../storage/cache/x_user_ids.json';
        $cache = [];

        if (file_exists($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true) ?? [];
        }

        // Check Cache
        if (isset($cache[$username])) {
            // echo "<li>User ID loaded from Cache: " . $cache[$username] . "</li>";
            return $cache[$username];
        }

        // Fetch from API
        $userUrl = "https://api.x.com/2/users/by/username/" . urlencode($username);
        $ch = curl_init($userUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $token, // Use Access Token here too
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SinappSocial/1.0');
        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($responseRaw, true);

        // Debug Output
        // echo "<li>User Lookup API Status: $httpCode</li>";
        if ($httpCode !== 200) {
            // echo "<h3>User Lookup Error (Usually 429 Rate Limit)</h3><pre>" . htmlspecialchars($responseRaw) . "</pre>";
            error_log("XProvider: User Lookup Error: " . $responseRaw);
        }

        if (isset($response['data']['id'])) {
            $userId = $response['data']['id'];

            // Save to Cache
            $cache[$username] = $userId;

            if (!is_dir(dirname($cacheFile))) {
                mkdir(dirname($cacheFile), 0777, true);
            }

            file_put_contents($cacheFile, json_encode($cache));
            // echo "<li>User ID fetched & cached: $userId</li>";
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

    public function post(string $content, array $mediaIds = []): bool
    {
        $this->refreshAccessTokenIfExpired();

        $apiUrl = "https://api.x.com/2/tweets";
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
        curl_setopt($ch, CURLOPT_USERAGENT, 'SinappSocial/1.0');

        $payload = ['text' => $content];
        if (!empty($mediaIds)) {
            $payload['media'] = ['media_ids' => $mediaIds];
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 201) {
            // echo "<h3>API Error (HTTP $httpCode)</h3>";
            // echo "<pre>" . htmlspecialchars($response) . "</pre>";
            error_log("XProvider::post FAILED. HTTP: $httpCode. Response: " . $response);
            error_log("Curl Error: " . curl_error($ch));
        }

        curl_close($ch);

        return $httpCode === 201;
    }

    public function uploadMedia(string $filePath): ?string
    {
        $this->refreshAccessTokenIfExpired();

        if (!file_exists($filePath)) {
            \App\Core\Logger::log("DEBUG: XProvider::uploadMedia - File not found: $filePath");
            return null;
        }

        // Use v1.1 endpoint (same as Python script)
        $apiUrl = "https://upload.twitter.com/1.1/media/upload.json";
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        // Determine media_category
        $isVideo = strpos($mimeType, 'video') !== false;
        $mediaCategory = $isVideo ? 'tweet_video' : 'tweet_image';

        // --- STEP 1: INIT ---
        $initFields = [
            'command' => 'INIT',
            'media_type' => $mimeType,
            'total_bytes' => $fileSize,
            'media_category' => $mediaCategory
        ];

        $initResponse = $this->makeUploadRequest($apiUrl, $initFields);
        if (!$initResponse || !isset($initResponse['media_id_string'])) {
            \App\Core\Logger::log("DEBUG: XProvider::uploadMedia INIT failed. Response: " . json_encode($initResponse));
            return null;
        }

        $mediaId = $initResponse['media_id_string'];
        \App\Core\Logger::log("DEBUG: XProvider::uploadMedia INIT success. Media ID: $mediaId");

        // --- STEP 2: APPEND ---
        $segmentIndex = 0;
        $file = fopen($filePath, 'rb');
        $chunkSize = 4 * 1024 * 1024; // 4MB chunks

        while (!feof($file)) {
            $chunk = fread($file, $chunkSize);

            // Check if chunk is empty (EOF)
            if (strlen($chunk) === 0) {
                break;
            }

            // Create temp file for CURLFile
            $tempChunkPath = sys_get_temp_dir() . '/upload_chunk_' . uniqid();
            file_put_contents($tempChunkPath, $chunk);

            $appendFields = [
                'command' => 'APPEND',
                'media_id' => $mediaId,
                'segment_index' => $segmentIndex,
                'media' => new \CURLFile($tempChunkPath)
            ];

            $appendResponse = $this->makeUploadRequest($apiUrl, $appendFields);

            unlink($tempChunkPath); // Cleanup

            // Append response is usually empty but check HTTP code implicit in makeUploadRequest
            if ($appendResponse === false) {
                \App\Core\Logger::log("DEBUG: XProvider::uploadMedia APPEND failed at segment $segmentIndex");
                fclose($file);
                return null;
            }

            $segmentIndex++;
        }
        fclose($file);
        \App\Core\Logger::log("DEBUG: XProvider::uploadMedia APPEND complete. Segments: $segmentIndex");

        // --- STEP 3: FINALIZE ---
        $finalizeFields = [
            'command' => 'FINALIZE',
            'media_id' => $mediaId
        ];

        $finalizeResponse = $this->makeUploadRequest($apiUrl, $finalizeFields);
        if (!$finalizeResponse || !isset($finalizeResponse['media_id_string'])) {
            \App\Core\Logger::log("DEBUG: XProvider::uploadMedia FINALIZE failed. Response: " . json_encode($finalizeResponse));
            return null;
        }

        // --- STEP 4: STATUS (Only if processing info is present) ---
        if (isset($finalizeResponse['processing_info'])) {
            $info = $finalizeResponse['processing_info'];
            $state = $info['state'];

            while ($state == 'pending' || $state == 'in_progress') {
                $checkAfter = $info['check_after_secs'] ?? 1;
                sleep($checkAfter);

                $statusFields = [
                    'command' => 'STATUS',
                    'media_id' => $mediaId
                ];

                $statusResponse = $this->makeUploadRequest($apiUrl, $statusFields, 'GET');

                if (!$statusResponse || !isset($statusResponse['processing_info'])) {
                    \App\Core\Logger::log("DEBUG: XProvider::uploadMedia STATUS check failed.");
                    break;
                }

                $info = $statusResponse['processing_info'];
                $state = $info['state'];

                if ($state == 'failed') {
                    \App\Core\Logger::log("DEBUG: XProvider::uploadMedia Processing FAILED. Error: " . json_encode($info['error'] ?? ''));
                    return null;
                }
            }
        }

        \App\Core\Logger::log("DEBUG: XProvider::uploadMedia FINALIZE success. Media ID: $mediaId");

        return $mediaId;
    }

    private function makeUploadRequest(string $url, array $fields, string $method = 'POST')
    {
        $ch = curl_init();

        $requestUrl = $url;
        if ($method === 'GET') {
            $requestUrl .= '?' . http_build_query($fields);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        }

        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $authHeader = "";

        // OAuth 1.0a check
        if (!empty($this->credentials['api_key']) && !empty($this->credentials['api_secret']) && !empty($this->credentials['access_token']) && !empty($this->credentials['access_token_secret'])) {
            // For GET requests, we need to pass query params for signature
            $queryParams = ($method === 'GET') ? $fields : [];
            $authHeader = $this->getOauth1Header($url, $method, $queryParams);
        } else {
            $accessToken = $this->credentials['access_token'] ?? '';
            $authHeader = "Bearer $accessToken";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: $authHeader"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $json ?: true;
        }

        \App\Core\Logger::log("DEBUG: Upload Request Failed. URL: $requestUrl. HTTP: $httpCode. Response: $response");
        return false;
    }

    private function getOauth1Header(string $url, string $method, array $queryParams = []): string
    {
        $oauth = [
            'oauth_consumer_key' => $this->credentials['api_key'] ?? '',
            'oauth_nonce' => md5(microtime() . mt_rand()),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $this->credentials['access_token'] ?? '',
            'oauth_version' => '1.0'
        ];

        // Combine oauth params with query params for signature calculation
        $signatureParams = array_merge($oauth, $queryParams);

        // Sort by key
        ksort($signatureParams);

        // Build key-value pairs for signature base string
        $params = [];
        foreach ($signatureParams as $key => $value) {
            $params[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }
        $paramString = implode('&', $params);

        // Build base string
        $baseString = strtoupper($method) . '&' . rawurlencode((string) $url) . '&' . rawurlencode($paramString);

        // Calculate signature
        $consumerSecret = $this->credentials['api_secret'] ?? '';
        $tokenSecret = $this->credentials['access_token_secret'] ?? '';

        $compositeKey = rawurlencode((string) $consumerSecret) . '&' . rawurlencode((string) $tokenSecret);
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $compositeKey, true));

        // Build header (only oauth_ params)
        $header = 'OAuth ';
        $values = [];
        ksort($oauth); // Ensure consistent order, though not strictly required for header
        foreach ($oauth as $key => $value) {
            $values[] = $key . '="' . rawurlencode((string) $value) . '"';
        }
        $header .= implode(', ', $values);

        return $header;
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
            // echo "<h3 style='color:red'>Access Token missing for user!</h3>";
            return [];
        }

        // echo "<li>Using Access Token: " . substr($accessToken, 0, 10) . "...</li>";

        // 2. Get User ID (Uses Cache first, then API with Access Token)
        // Pass Access Token to use User Context limits for lookup too
        $userId = $this->getUserId($username, $accessToken);

        if (!$userId) {
            // echo "<h3 style='color:red'>Could not resolve User ID for $username</h3>";
            return [];
        }

        // 3. Fetch User Timeline (users/:id/tweets)
        // Rate Limit: 1500 requests / 15 mins (User Context) vs 180 for Search
        // Minimum max_results is 5
        $apiLimit = max(5, $limit);
        $tweetsUrl = "https://api.x.com/2/users/$userId/tweets?max_results=$apiLimit&tweet.fields=created_at&exclude=retweets,replies";

        $headers = [];
        $ch = curl_init($tweetsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $accessToken,
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SinappSocial/1.0');
        // Callback to capture headers
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2)
                return $len;
            $headers[trim($header[0])] = trim($header[1]);
            return $len;
        });

        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Echo debug info
        // echo "<li>Timeline API Status: $httpCode</li>";

        // Echo Rate Limit Headers
        if (isset($headers['x-rate-limit-limit'])) {
            // echo "<li>Rate Limit: " . $headers['x-rate-limit-remaining'] . " / " . $headers['x-rate-limit-limit'] . "</li>";
            // echo "<li>Reset Time: " . date('H:i:s', $headers['x-rate-limit-reset']) . "</li>";
        } else {
            // echo "<li>No Rate Limit Headers found (Possible Auth/Plan Issue)</li>";
        }

        if ($httpCode !== 200) {
            // echo "<h3>Timeline API Error</h3><pre>" . htmlspecialchars($responseRaw) . "</pre>";
            error_log("XProvider::searchRecentPosts FAILED. HTTP: $httpCode. Response: " . $responseRaw);
            return [];
        }

        $data = json_decode($responseRaw, true);
        return array_slice($data['data'] ?? [], 0, $limit);
    }

    public function reply(string $postId, string $content): bool
    {
        $this->refreshAccessTokenIfExpired();

        $apiUrl = "https://api.x.com/2/tweets";
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
        curl_setopt($ch, CURLOPT_USERAGENT, 'SinappSocial/1.0');

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

    public function checkToken(): array
    {
        $this->refreshAccessTokenIfExpired();
        $accessToken = $this->credentials['access_token'] ?? null;

        if (empty($accessToken)) {
            return ['error' => 'No Access Token'];
        }

        $url = "https://api.x.com/2/users/me";
        $headers = [];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $accessToken,
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SinappSocial/1.0');

        // Capture headers
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2)
                return $len;
            $headers[trim($header[0])] = trim($header[1]);
            return $len;
        });

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'response' => json_decode($response, true),
            'headers' => $headers
        ];
    }
}
