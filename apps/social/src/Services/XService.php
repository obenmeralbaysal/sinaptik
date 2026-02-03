<?php

namespace App\Services;

use App\Core\Config;

class XService
{
    private $clientId;
    private $clientSecret;
    private $callbackUrl;

    public function __construct()
    {
        $this->clientId = $_ENV['X_CLIENT_ID'] ?? '';
        $this->clientSecret = $_ENV['X_CLIENT_SECRET'] ?? '';
        $this->callbackUrl = $_ENV['X_CALLBACK_URL'] ?? '';
    }

    public function getAuthUrl($state, $forceLogin = false)
    {
        // PKCE - In a production app, we should store code_verifier in session
        $codeChallenge = 'challenge'; // Simplified for now, should be SHA256 of verifier

        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->callbackUrl,
            'scope' => 'tweet.read tweet.write users.read offline.access',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'plain'
        ];

        if ($forceLogin) {
            $params['prompt'] = 'login';
            // specific workaround: some reports suggest force_login might still trigger legacy behaviors or be required in edge cases
            // putting it back alongside prompt=login to be safe
            $params['force_login'] = 'true';
        }

        return "https://twitter.com/i/oauth2/authorize?" . http_build_query($params);
    }

    public function getTokens($code)
    {
        $ch = curl_init('https://api.twitter.com/2/oauth2/token');

        $postData = [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->callbackUrl,
            'code_verifier' => 'challenge'
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $result = json_decode($response, true);
        curl_close($ch);

        return $result;
    }

    public function refreshTokens($refreshToken)
    {
        $ch = curl_init('https://api.twitter.com/2/oauth2/token');

        $postData = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $result = json_decode($response, true);
        curl_close($ch);

        return $result;
    }

    public function getUser($accessToken)
    {
        $ch = curl_init('https://api.twitter.com/2/users/me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $result = json_decode($response, true);
        curl_close($ch);

        return $result;
    }

    public function checkConnectivity(?string $accessToken)
    {
        if (!$accessToken) {
            return ['status' => 'error', 'message' => 'No access token available'];
        }

        $ch = curl_init('https://api.twitter.com/2/users/me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_HEADER, true); // We need headers

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headerStr = substr($response, 0, $headerSize);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Parse headers for rate limits
        $headers = [];
        foreach (explode("\r\n", $headerStr) as $line) {
            if (strpos($line, ': ') !== false) {
                list($key, $value) = explode(': ', $line, 2);
                $headers[strtolower($key)] = $value;
            }
        }

        return [
            'status' => $httpCode === 200 ? 'online' : 'error',
            'http_code' => $httpCode,
            'limit' => $headers['x-rate-limit-limit'] ?? 'N/A',
            'remaining' => $headers['x-rate-limit-remaining'] ?? 'N/A',
            'reset' => isset($headers['x-rate-limit-reset']) ? date('H:i:s', $headers['x-rate-limit-reset']) : 'N/A'
        ];
    }
}
