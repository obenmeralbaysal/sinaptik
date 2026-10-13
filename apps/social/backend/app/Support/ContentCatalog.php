<?php

namespace App\Support;

class ContentCatalog
{
    public static function prompt(string $group, ?string $key, string $fallback): string
    {
        if ($key === null || $key === '') {
            return $fallback;
        }

        $prompt = config("content.{$group}.{$key}.prompt");

        return is_string($prompt) && $prompt !== '' ? $prompt : $key;
    }
}
