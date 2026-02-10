<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load .env
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

$aiService = new \App\Services\AIService();
echo "Provider: " . (getenv('GEMINI_API_KEY') ? 'Gemini' : 'OpenAI') . "\n";
echo "API Key Length: " . strlen(getenv('GEMINI_API_KEY') ?: getenv('OPENAI_API_KEY')) . "\n";

try {
    $models = $aiService->getAvailableModels();
    echo "Available Models:\n";
    print_r($models);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
