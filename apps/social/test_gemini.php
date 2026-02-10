<?php
// Standalone script to test Gemini API
// Usage: php test_gemini.php

require_once __DIR__ . '/vendor/autoload.php';

// Manually load env for testing if needed, or rely on system envs
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$apiKey = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');

if (!$apiKey) {
    die("Error: GEMINI_API_KEY is not set in environment or .env file.\n");
}

echo "Using API Key: " . substr($apiKey, 0, 5) . "...\n";

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $apiKey;
$data = [
    'contents' => [
        ['parts' => [['text' => "Hello, reply with 'Gemini is working!'"]]]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Skip SSL verification for local testing if needed (CAUTION: Insecure)
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("Curl Error: " . curl_error($ch) . "\n");
}

curl_close($ch);

$json = json_decode($response, true);

if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
    echo "Success! Response: " . $json['candidates'][0]['content']['parts'][0]['text'] . "\n";
} else {
    echo "Failed to get valid response.\n";
    echo "Raw Response: " . $response . "\n";
}
