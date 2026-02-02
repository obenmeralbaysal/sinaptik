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

    public function getAuthUrl($state)
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
}
