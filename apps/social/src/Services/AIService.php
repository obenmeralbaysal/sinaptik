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
        // Prompt engineering based on tone or custom instruction
        if ($customPrompt) {
            $prompt = "Context: '$originalContent'\nInstruction: $customPrompt\n\nTask: Write exactly ONE (1) reply tweet based on the instruction. Do not give options. Do not use quotes. Return ONLY the tweet text.";
        } else {
            // Fallback if tone is 'custom' but no prompt provided
            $effectiveTone = ($tone === 'custom') ? 'neutral' : $tone;
            $prompt = "Respond to this tweet: '$originalContent' in a $effectiveTone tone. Write exactly ONE (1) reply. Do not give options. Return ONLY the text.";
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

        error_log("AIService: Calling Gemini API: $url");
        // error_log("AIService: Payload: " . json_encode($data));

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        error_log("AIService: Gemini HTTP $httpCode Response: " . substr($responseRaw, 0, 500));

        $response = json_decode($responseRaw, true);

        if (isset($response['error'])) {
            $msg = $response['error']['message'] ?? 'Unknown API Error';
            if (isset($response['error']['code']) && $response['error']['code'] == 429) {
                throw new \Exception("Gemini Quota Exceeded: " . $msg);
            }
            throw new \Exception("Gemini API Error: " . $msg);
        }

        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $response['candidates'][0]['content']['parts'][0]['text'];
        }

        error_log("AIService: Parsed error or missing content: " . print_r($response, true));

        throw new \Exception("Unable to generate response. Invalid API output.");
    }

    private function callOpenAI(string $prompt): string
    {
        $url = "https://api.openai.com/v1/chat/completions";
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 2000 // Increased for batch
        ];

        // error_log("AIService: Calling OpenAI...");

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

    public function getAvailableImageModels(): array
    {
        if ($this->provider === 'openai') {
            return ['dall-e-3', 'dall-e-2'];
        }

        // Gemini logic
        if (empty($this->apiKey)) {
            // Fallback
            return ['imagen-3.0-generate-001'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $this->apiKey;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $rawResponse = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($rawResponse, true);
        $models = [];

        if (isset($response['models'])) {
            foreach ($response['models'] as $model) {
                $methods = $model['supportedGenerationMethods'] ?? [];
                $name = str_replace('models/', '', $model['name']);

                // Check if it supports image generation or looks like an imagen model
                if (in_array('imageGeneration', $methods) || strpos($name, 'imagen') !== false) {
                    $models[] = $name;
                }
            }
        }

        // Ensure known working models are present if API doesn't list them
        $known = ['imagen-3.0-generate-001'];
        $models = array_unique(array_merge($models, $known));

        return $models;
    }

    public function generateImage(string $prompt, string $model = 'imagen-3.0-generate-001', string $size = '1024x1024'): ?string
    {
        if ($this->provider === 'gemini') {
            return $this->callGeminiImage($prompt, $model, $size);
        }

        // Fallback to OpenAI DALL-E logic if provider is openai
        $url = "https://api.openai.com/v1/images/generations";

        $data = [
            'model' => $model === 'imagen-3.0-generate-001' ? 'dall-e-3' : $model, // Fallback mapping
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size
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

        if (isset($response['data'][0]['url'])) {
            return $response['data'][0]['url'];
        }

        if (isset($response['error']['message'])) {
            throw new \Exception("Image Generation Error: " . $response['error']['message']);
        }

        return null;
    }

    private function callGeminiImage(string $prompt, string $model, string $size): string
    {
        // Gemini Image Generation (Imagen) via REST API
        // Note: The endpoint and payload format for Imagen on Vertex AI/Gemini API might differ.
        // As of late 2024/2025, it usually requires a specific endpoint.
        // Assuming usage of Google Generative AI API:

        // This is a placeholder for the actual Imagen API call as it varies by specific Google Cloud/AI Studio setup.
        // Using a standard predict pattern often seen:

        $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:predict?key=" . $this->apiKey;

        // Construct payload for Imagen
        $data = [
            'instances' => [
                ['prompt' => $prompt]
            ],
            'parameters' => [
                'sampleCount' => 1,
                'aspectRatio' => '1:1' // Mapping 1024x1024 to aspect ratio
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $responseRaw = curl_exec($ch);
        $response = json_decode($responseRaw, true);
        curl_close($ch);

        // Check for Base64 image in response
        if (isset($response['predictions'][0]['bytesBase64Encoded'])) {
            $base64 = $response['predictions'][0]['bytesBase64Encoded'];
            $mime = $response['predictions'][0]['mimeType'] ?? 'image/png';

            // To be usable as a URL, we might need to upload this or return a data URI.
            // For now, let's return a Data URI.
            // CAUTION: Data URIs can be large for DB storage. Ideally, upload to local storage.
            // But user didn't specify storage, so let's try data URI or save to file.

            // Saving to file recommended
            $filename = 'gen_' . uniqid() . '.png';
            $filepath = __DIR__ . '/../../public/uploads/' . $filename;
            if (!is_dir(dirname($filepath)))
                mkdir(dirname($filepath), 0777, true);

            file_put_contents($filepath, base64_decode($base64));

            return '/uploads/' . $filename;
        }

        // Error handling
        if (isset($response['error']['message'])) {
            throw new \Exception("Gemini Image Error: " . $response['error']['message']);
        }

        // Fallback or debug
        throw new \Exception("Failed to generate image with Gemini. Response: " . substr($responseRaw, 0, 200));
    }
}
