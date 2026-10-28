<?php

use App\Models\SocialAccount;
use App\Support\WorkspaceContext;
use Tests\Support\World;

function metaSigned(array $data, string $secret = 'test-secret'): string
{
    $payload = rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
    $sig = rtrim(strtr(base64_encode(hash_hmac('sha256', $payload, $secret, true)), '+/', '-_'), '=');

    return $sig.'.'.$payload;
}

it('confirms a signed data deletion request and disconnects on deauthorize', function () {
    $world = World::boot();
    app(WorkspaceContext::class)->activate($world->workspace->id);
    SocialAccount::query()->create([
        'workspace_id' => $world->workspace->id,
        'platform' => 'meta_user',
        'external_id' => '555',
        'name' => 'Kullanıcı',
        'access_token' => 'secret-token',
        'status' => 'connected',
    ]);

    $this->post('/api/meta/data-deletion', [
        'signed_request' => metaSigned(['user_id' => '555', 'algorithm' => 'HMAC-SHA256']),
    ])->assertOk()->assertJsonStructure(['url', 'confirmation_code']);

    expect(SocialAccount::query()->withoutGlobalScopes()->first()->status)->toBe('disconnected');
    expect(SocialAccount::query()->withoutGlobalScopes()->first()->access_token)->toBeNull();

    SocialAccount::query()->withoutGlobalScopes()->update([
        'status' => 'connected',
        'access_token' => 'secret-token',
    ]);

    $this->post('/api/meta/deauthorize', [
        'signed_request' => metaSigned(['user_id' => '555']),
    ])->assertOk();
    expect(SocialAccount::query()->withoutGlobalScopes()->first()->status)->toBe('disconnected');

    $this->post('/api/meta/data-deletion', [
        'signed_request' => 'bad.signature',
    ])->assertStatus(400);
});
