<?php

namespace App\Services\AI;

use App\Contracts\AIDriver;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIDriver implements AIDriver
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

        $response = Http::withToken($this->apiKey)
            ->asJson()
            ->post($this->baseUrl.'/chat/completions', [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.7,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI error: '.$response->body());
        }

        $text = $response->json('choices.0.message.content');
        if (! $text) {
            throw new RuntimeException('OpenAI returned no content.');
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
        $size = match ($aspectRatio) {
            '16:9' => '1792x1024',
            '9:16' => '1024x1792',
            default => '1024x1024',
        };

        $response = Http::withToken($this->apiKey)
            ->asJson()
            ->post($this->baseUrl.'/images/generations', [
                'model' => $model,
                'prompt' => $prompt,
                'n' => 1,
                'size' => $size,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI image error: '.$response->body());
        }

        return $response->json('data.0.url');
    }

    public function listTextModels(): array
    {
        return ['gpt-4o-mini', 'gpt-4o', 'gpt-4-turbo', 'gpt-3.5-turbo'];
    }

    public function listImageModels(): array
    {
        return ['dall-e-3', 'dall-e-2'];
    }
}
