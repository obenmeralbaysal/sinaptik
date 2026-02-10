<?php

namespace App\Services;

use App\Core\Config;

class AIService
{
    private ?string $apiKey;
    private string $provider; // 'gemini' or 'openai'

    public function __construct()
    {
        $this->apiKey = Config::get('GEMINI_API_KEY') ?: Config::get('OPENAI_API_KEY');
        $this->provider = Config::get('GEMINI_API_KEY') ? 'gemini' : 'openai';

        if (empty($this->apiKey)) {
            // Log warning but don't crash distinctively yet - methods will handle it
            error_log("AIService: No AI API Key found in environment.");
        }
    }

    public function generatePost(string $topic): ?string
    {
        // Prompt logic for creating a new post
        $prompt = "Write an engaging social media post about $topic. Keep it concise.";
        if ($this->provider === 'gemini') {
            return $this->callGemini($prompt);
        } else {
            return $this->callOpenAI($prompt);
        }
    }

    public function generateBatchPosts(array $criteria, int $count): array
    {
        $prompt = "You are a social media assistant.
        Role/Persona: {$criteria['persona']}
        Tone: {$criteria['mode']}
        Language: {$criteria['language']}
        Topic: {$criteria['topic']}
        
        Generate exactly $count distinct, creative, and engaging X (Twitter) posts based on these criteria.
        Ensure they vary in structure and phrasing.
        Return ONLY a valid JSON array of strings. Do not include markdown formatting (like ```json).";

        if ($this->provider === 'gemini') {
            $model = $criteria['model'] ?? 'gemini-1.5-flash';
            $response = $this->callGemini($prompt, $model);
        } else {
            $response = $this->callOpenAI($prompt);
        }

        // Clean up markdown code blocks if present
        $response = str_replace(['```json', '```'], '', $response);
        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function generateReply(string $originalContent, string $tone = 'supportive', ?string $customPrompt = null, string $model = 'gemini-1.5-flash'): ?string
    {
        // Prompt engineering based on tone or custom instruction
        if ($customPrompt) {
            $prompt = "Context: $originalContent\nInstruction: $customPrompt";
        } else {
            // Fallback if tone is 'custom' but no prompt provided
            $effectiveTone = ($tone === 'custom') ? 'neutral' : $tone;
            $prompt = "Respond to this message: '$originalContent' in a $effectiveTone tone.";
        }

        if ($this->provider === 'gemini') {
            return $this->callGemini($prompt, $model);
        } else {
            return $this->callOpenAI($prompt);
        }
    }

    private function callGemini(string $prompt, string $model = 'gemini-1.5-flash'): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=" . $this->apiKey;
        $data = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response['candidates'][0]['content']['parts'][0]['text'] ?? "Unable to generate response.";
    }

    private function callOpenAI(string $prompt): string
    {
        $url = "https://api.openai.com/v1/chat/completions";
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 100
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response['choices'][0]['message']['content'] ?? "Unable to generate response.";
    }
    public function getAvailableModels(): array
    {
        if (empty($this->apiKey)) {
            return ['Error: Missing API Key - Check .env'];
        }

        $models = [];
        // ... rest of implementation (OpenAI / Gemini logic) ...

        if ($this->provider === 'openai') {
            // Attempt to fetch OpenAI models
            $url = "https://api.openai.com/v1/models";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $this->apiKey
            ]);
            $response = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (isset($response['data'])) {
                foreach ($response['data'] as $model) {
                    // Filter for chat models if possible, or just list them
                    if (strpos($model['id'], 'gpt') === 0) {
                        $models[] = $model['id'];
                    }
                }
            }

            if (empty($models)) {
                $models = ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo'];
            }
            return $models;
        }

        // Gemini Logic
        $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $this->apiKey;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $rawResponse = curl_exec($ch);
        $response = json_decode($rawResponse, true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !isset($response['models'])) {
            error_log("AIService: Failed to fetch Gemini models. HTTP Code: $httpCode. Response: " . substr($rawResponse, 0, 200));
        }

        if (isset($response['models'])) {
            foreach ($response['models'] as $model) {
                if (isset($model['supportedGenerationMethods']) && in_array('generateContent', $model['supportedGenerationMethods'])) {
                    $models[] = str_replace('models/', '', $model['name']);
                }
            }
        }

        // Fallback if API fails or empty
        if (empty($models)) {
            $models = ['gemini-1.5-flash', 'gemini-pro', 'gemini-1.5-pro'];
        }

        return $models;
    }
}
