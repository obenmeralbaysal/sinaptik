<?php

namespace App\Contracts;

interface AIDriver
{
    public function generateText(string $prompt, ?string $model = null): string;

    public function generateBatch(string $prompt, ?string $model = null): array;

    public function generateImage(string $prompt, ?string $model = null, string $aspectRatio = '1:1'): ?string;

    public function listTextModels(): array;

    public function listImageModels(): array;
}
