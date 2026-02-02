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
    }

    public function generatePost(string $topic): ?string
    {
        // Prompt logic for creating a new post
        return "This is an AI generated post about $topic.";
    }

    public function generateReply(string $originalContent, string $tone = 'supportive'): ?string
    {
        // Prompt engineering based on tone
        $prompt = "Respond to this message: '$originalContent' in a $tone tone.";
        
        if ($this->provider === 'gemini') {
            return $this->callGemini($prompt);
        } else {
            return $this->callOpenAI($prompt);
        }
    }

    private function callGemini(string $prompt): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $this->apiKey;
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
}
