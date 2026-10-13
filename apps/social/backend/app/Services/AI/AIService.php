<?php

namespace App\Services\AI;

use App\Contracts\AIDriver;
use App\Support\ContentCatalog;

class AIService
{
    public function __construct(private readonly AIDriver $driver) {}

    public function generatePost(string $topic, ?string $model = null): string
    {
        $prompt = "Write an engaging X (Twitter) post about: {$topic}. Keep it under 280 characters. Return ONLY the tweet text, no quotes, no markdown.";
        return trim($this->driver->generateText($prompt, $model));
    }

    public function generateBatchPosts(array $criteria, int $count, ?string $model = null): array
    {
        $persona = ContentCatalog::prompt('personas', $criteria['persona'] ?? null, 'A thoughtful creator sharing a personal take.');
        $tone = ContentCatalog::prompt('tones', $criteria['tone'] ?? null, 'warm, encouraging, and clearly on the reader\'s side');
        $language = ContentCatalog::prompt('languages', $criteria['language'] ?? null, 'English');
        $topic = $criteria['topic'] ?? 'social media';

        $prompt = <<<PROMPT
You are a social media assistant.
Persona: {$persona}
Tone: {$tone}
Language: {$language}
Topic: {$topic}

Generate exactly {$count} distinct, creative X (Twitter) posts based on these criteria.
Vary structure and phrasing. Each post must be under 280 characters.
Return ONLY a valid JSON array of strings. No markdown fences.
PROMPT;

        $items = $this->driver->generateBatch($prompt, $model);
        return array_values(array_filter(array_map('trim', $items)));
    }

    public function generateReply(string $originalContent, string $tone = 'supportive', ?string $customPrompt = null, ?string $model = null): string
    {
        if ($customPrompt) {
            $prompt = "Context tweet: '{$originalContent}'\nInstruction: {$customPrompt}\n\nWrite exactly ONE reply tweet (no quotes, no options). Keep it under 280 characters.";
        } else {
            $effectiveTone = $tone === 'custom'
                ? 'calm and factual, with no emotional coloring'
                : ContentCatalog::prompt('tones', $tone, 'warm, encouraging, and clearly on the reader\'s side');
            $prompt = "Reply to this tweet: '{$originalContent}' in a {$effectiveTone} tone. Write exactly ONE reply (no quotes, no options). Keep it under 280 characters.";
        }

        return trim($this->driver->generateText($prompt, $model));
    }

    public function generateImage(string $prompt, ?string $model = null, string $aspectRatio = '1:1'): ?string
    {
        return $this->driver->generateImage($prompt, $model, $aspectRatio);
    }

    public function listTextModels(): array
    {
        return $this->driver->listTextModels();
    }

    public function listImageModels(): array
    {
        return $this->driver->listImageModels();
    }
}
