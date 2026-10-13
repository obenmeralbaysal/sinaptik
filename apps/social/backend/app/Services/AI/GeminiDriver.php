<?php

namespace App\Services\AI;

use App\Contracts\AIDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GeminiDriver implements AIDriver
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $defaultTextModel,
        private readonly string $defaultImageModel,
    ) {
    }

    public function generateText(string $prompt, ?string $model = null): string
    {
        $model ??= $this->defaultTextModel;
        $url = $this->baseUrl."/models/{$model}:generateContent?key=".$this->apiKey;

        $response = Http::asJson()->post($url, [
            'contents' => [['parts' => [['text' => $prompt]]]],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini error: '.$response->body());
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        if (! $text) {
            throw new RuntimeException('Gemini returned no content.');
        }

        return $text;
    }

    public function generateBatch(string $prompt, ?string $model = null): array
    {
        $raw = $this->generateText($prompt, $model);
        $clean = trim(preg_replace('/^```json|```$/m', '', $raw));
        $decoded = json_decode($clean, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function generateImage(string $prompt, ?string $model = null, string $aspectRatio = '1:1'): ?string
    {
        $model ??= $this->defaultImageModel;
        $url = $this->baseUrl."/models/{$model}:predict?key=".$this->apiKey;

        $response = Http::asJson()->post($url, [
            'instances' => [['prompt' => $prompt]],
            'parameters' => ['sampleCount' => 1, 'aspectRatio' => $aspectRatio],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini image error: '.$response->body());
        }

        $base64 = $response->json('predictions.0.bytesBase64Encoded');
        if (! $base64) {
            return null;
        }

        $filename = 'ai-images/'.uniqid('gemini_', true).'.png';
        Storage::disk('public')->put($filename, base64_decode($base64));

        return Storage::disk('public')->url($filename);
    }

    public function listTextModels(): array
    {
        $models = $this->fetchModels('generateContent');
        if (empty($models)) {
            return ['gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-1.5-pro'];
        }
        return $models;
    }

    public function listImageModels(): array
    {
        $models = $this->fetchModels('predict');
        if (empty($models)) {
            return ['gemini-2.5-flash-image', 'imagen-3.0-generate-001'];
        }
        return $models;
    }

    private function fetchModels(string $method): array
    {
        $url = $this->baseUrl.'/models?key='.$this->apiKey;
        $response = Http::get($url);
        if (! $response->successful()) {
            return [];
        }

        $models = [];
        foreach ($response->json('models', []) ?? [] as $m) {
            $methods = $m['supportedGenerationMethods'] ?? [];
            if (in_array($method, $methods, true)) {
                $models[] = str_replace('models/', '', $m['name']);
            }
        }
        sort($models);
        return $models;
    }
}
