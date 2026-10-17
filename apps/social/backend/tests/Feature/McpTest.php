<?php

use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Support\WorkspaceContext;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\Support\World;

function mcpCall(string $token, string $method, array $params = [], ?int $id = 1)
{
    return test()->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => $id,
        'method' => $method,
        'params' => $params,
    ], ['Authorization' => 'Bearer '.$token]);
}

it('authenticates mcp keys, enforces scopes, rate limits, and writes an audit log', function () {
    $world = World::boot();
    Redis::flushdb();

    mcpCall('smk_missing', 'initialize')->assertUnauthorized();

    app(WorkspaceContext::class)->activate($world->workspace->id);
    $plain = 'smk_'.Str::random(40);
    ApiKey::query()->create([
        'workspace_id' => $world->workspace->id,
        'name' => 'Ajan',
        'prefix' => substr($plain, 0, 12),
        'key_hash' => hash('sha256', $plain),
        'scopes' => ['posts:read'],
        'created_by' => $world->owner->id,
    ]);

    mcpCall($plain, 'initialize')->assertOk()->assertJsonPath('result.serverInfo.name', 'sinapp-social');
    mcpCall($plain, 'tools/call', ['name' => 'create_post', 'arguments' => ['body' => 'x']], 2)->assertForbidden();

    $plan = Plan::query()->where('code', 'pro')->first();
    $limits = $plan->limits;
    $limits['mcp_calls_per_minute'] = 2;
    $plan->update(['limits' => $limits]);

    Redis::flushdb();
    mcpCall($plain, 'ping', [], 3)->assertOk();
    mcpCall($plain, 'ping', [], 4)->assertOk();
    mcpCall($plain, 'ping', [], 5)->assertStatus(429);

    expect(AuditLog::query()->where('tool', 'initialize')->where('result', 'ok')->exists())->toBeTrue();
    expect(AuditLog::query()->where('tool', 'create_post')->where('result', 'denied')->exists())->toBeTrue();
});
