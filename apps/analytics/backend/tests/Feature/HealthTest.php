<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoints_respond(): void
    {
        $this->get('/up')->assertOk();
        $this->getJson('/api/health')->assertOk()->assertJsonPath('status', 'ok');
    }
}
