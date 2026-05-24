<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\AI\GeminiDriver;
use App\Services\AI\OpenAIDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIManagerTenantKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_tenant_gemini_key_over_system_config(): void
    {
        config()->set('ai.driver', 'gemini');
        config()->set('ai.gemini.api_key', 'system-key');
        config()->set('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        config()->set('ai.gemini.default_text_model', 'gemini-1.5-flash');
        config()->set('ai.gemini.default_image_model', 'imagen-3.0-generate-001');

        $owner = User::factory()->create();
        $org = Organization::create([
            'slug' => 'tenant-gem',
            'name' => 'Tenant Gem',
            'owner_id' => $owner->id,
            'ai_driver' => 'gemini',
            'ai_gemini_api_key' => 'TENANT-GEMINI-KEY',
        ]);

        Http::fake([
            '*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'hello']]]]],
            ], 200),
        ]);

        $ai = app(AIManager::class)->forOrganization($org);
        $ai->generatePost('test');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'key=TENANT-GEMINI-KEY')
                && ! str_contains($request->url(), 'key=system-key');
        });
    }

    public function test_auto_picks_openai_when_only_openai_key_present(): void
    {
        config()->set('ai.driver', 'gemini'); // system default

        config()->set('ai.openai.base_url', 'https://api.openai.com/v1');
        config()->set('ai.openai.default_text_model', 'gpt-4o-mini');
        config()->set('ai.openai.default_image_model', 'dall-e-3');

        $owner = User::factory()->create();
        $org = Organization::create([
            'slug' => 'tenant-oai',
            'name' => 'Tenant OAI',
            'owner_id' => $owner->id,
            // no ai_driver set
            'ai_openai_api_key' => 'sk-TENANT',
        ]);

        Http::fake([
            '*' => Http::response([
                'choices' => [['message' => ['content' => 'hello']]],
            ], 200),
        ]);

        $ai = app(AIManager::class)->forOrganization($org);
        $ai->generatePost('test');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.openai.com')
                && $request->hasHeader('Authorization', 'Bearer sk-TENANT');
        });
    }

    public function test_throws_when_no_key_configured_for_chosen_provider(): void
    {
        config()->set('ai.driver', 'gemini');
        config()->set('ai.gemini.api_key', null);

        $owner = User::factory()->create();
        $org = Organization::create([
            'slug' => 'tenant-empty',
            'name' => 'Empty',
            'owner_id' => $owner->id,
            'ai_driver' => 'gemini',
        ]);

        $this->expectException(\RuntimeException::class);
        app(AIManager::class)->forOrganization($org);
    }
}
