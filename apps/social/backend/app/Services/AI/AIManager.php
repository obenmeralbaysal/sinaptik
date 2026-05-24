<?php

namespace App\Services\AI;

use App\Contracts\AIDriver;
use App\Models\Organization;
use RuntimeException;

class AIManager
{
    /**
     * Build an AIService for a specific organization, using the org's
     * own API keys when configured. Falls back to the system .env config
     * only if the org has not provided keys (helpful for dev / shared mode).
     */
    public function forOrganization(Organization $org): AIService
    {
        $driver = $this->resolveDriverName($org);
        return new AIService($this->buildDriver($driver, $org));
    }

    /**
     * Resolve the AIService for the currently bound tenant
     * (set by the ResolveOrganization middleware).
     */
    public function forCurrentOrganization(): AIService
    {
        if (! app()->bound('currentOrganization')) {
            throw new RuntimeException('No tenant is bound; cannot resolve AI service.');
        }
        return $this->forOrganization(app('currentOrganization'));
    }

    /**
     * Build a system-default service (used by global/admin contexts only).
     */
    public function default(): AIService
    {
        $driver = config('ai.driver', 'gemini');
        return new AIService($this->buildDriverFromConfig($driver));
    }

    private function resolveDriverName(Organization $org): string
    {
        if ($org->ai_driver) {
            return $org->ai_driver;
        }

        // Auto-pick: if tenant only has one key configured, use that driver.
        if ($org->ai_gemini_api_key && ! $org->ai_openai_api_key) {
            return 'gemini';
        }
        if ($org->ai_openai_api_key && ! $org->ai_gemini_api_key) {
            return 'openai';
        }

        return config('ai.driver', 'gemini');
    }

    private function buildDriver(string $driver, Organization $org): AIDriver
    {
        return match ($driver) {
            'openai' => new OpenAIDriver(
                $this->mustHave($org->ai_openai_api_key ?? config('ai.openai.api_key'), 'OpenAI', $org),
                config('ai.openai.base_url'),
                $org->ai_default_text_model ?: config('ai.openai.default_text_model'),
                $org->ai_default_image_model ?: config('ai.openai.default_image_model'),
            ),
            default => new GeminiDriver(
                $this->mustHave($org->ai_gemini_api_key ?? config('ai.gemini.api_key'), 'Gemini', $org),
                config('ai.gemini.base_url'),
                $org->ai_default_text_model ?: config('ai.gemini.default_text_model'),
                $org->ai_default_image_model ?: config('ai.gemini.default_image_model'),
            ),
        };
    }

    private function buildDriverFromConfig(string $driver): AIDriver
    {
        return match ($driver) {
            'openai' => new OpenAIDriver(
                (string) (config('ai.openai.api_key') ?? ''),
                config('ai.openai.base_url'),
                config('ai.openai.default_text_model'),
                config('ai.openai.default_image_model'),
            ),
            default => new GeminiDriver(
                (string) (config('ai.gemini.api_key') ?? ''),
                config('ai.gemini.base_url'),
                config('ai.gemini.default_text_model'),
                config('ai.gemini.default_image_model'),
            ),
        };
    }

    private function mustHave(?string $key, string $providerName, Organization $org): string
    {
        if (! $key) {
            throw new RuntimeException(
                "{$providerName} API key is not configured for organization '{$org->slug}'. ".
                "Add it under Settings → AI Settings."
            );
        }
        return $key;
    }
}
