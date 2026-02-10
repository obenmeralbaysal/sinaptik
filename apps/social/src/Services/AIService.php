<?php

namespace App\Services;

use App\Core\Config;

class AIService
{
    private string $apiKey;
    private string $provider; // 'gemini' or 'openai'

    public function __construct()
    {
        $this->apiKey = Config::get('GEMINI_API_KEY') ?: Config::get('OPENAI_API_KEY');
        $this->provider = Config::get('GEMINI_API_KEY') ? 'gemini' : 'openai';

        if (empty($this->apiKey)) {
            throw new \Exception("No AI API Key found. Please configure GEMINI_API_KEY or OPENAI_API_KEY in .env file.");
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
            $prompt = "Respond to this message: '$originalContent' in a $tone tone.";
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
        if ($this->provider === 'openai') {
            return ['gpt-3.5-turbo', 'gpt-4'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $this->apiKey;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $models = [];
        if (isset($response['models'])) {
            foreach ($response['models'] as $model) {
                if (isset($model['supportedGenerationMethods']) && in_array('generateContent', $model['supportedGenerationMethods'])) {
                    $models[] = str_replace('models/', '', $model['name']);
                }
            }
        }

        // Fallback if API fails or empty
        if (empty($models)) {
            $models = ['gemini-1.5-flash', 'gemini-pro'];
        }

        return $models;
    }
}
