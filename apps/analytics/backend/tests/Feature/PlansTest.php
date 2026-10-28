<?php

namespace Tests\Feature;

use Tests\TestCase;

class PlansTest extends TestCase
{
    public function test_sinapp_plan_catalog_is_public(): void
    {
        $response = $this->getJson('/api/sinapp/plans')->assertOk();

        $response->assertJsonPath('product', 'sinaptik-analytics');
        $response->assertJsonPath('currency', 'TRY');
        $response->assertJsonPath('plans.0.code', 'analytics-starter-monthly');
        $response->assertJsonPath('plans.1.code', 'analytics-pro-monthly');
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
        $this->assertArrayNotHasKey('site_limit', $response->json('plans.0'));
    }
}
