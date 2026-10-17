<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Support\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    private array $scopes = [
        'posts:read', 'posts:write', 'media:write', 'analytics:read',
        'ads:read', 'ads:write', 'ads:budget', 'approvals:write',
    ];

    public function index()
    {
        return response()->json([
            'keys' => ApiKey::query()->latest('id')->get()->map(fn (ApiKey $key) => [
                'id' => $key->id,
                'name' => $key->name,
                'prefix' => $key->prefix,
                'scopes' => $key->scopes,
                'brand_id' => $key->brand_id,
                'last_used_at' => $key->last_used_at,
                'last_used_ip' => $key->last_used_ip,
                'revoked_at' => $key->revoked_at,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        if (! Role::canManageTeam($request->attributes->get('membership')->role)) {
            abort(403);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', 'in:'.implode(',', $this->scopes)],
            'brand_id' => ['nullable', 'integer'],
        ]);
        $plain = 'smk_'.Str::random(40);
        $key = ApiKey::query()->create([
            'name' => $data['name'],
            'prefix' => substr($plain, 0, 12),
            'key_hash' => hash('sha256', $plain),
            'scopes' => $data['scopes'],
            'brand_id' => $data['brand_id'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['key' => $key->only(['id', 'name', 'prefix', 'scopes']), 'token' => $plain], 201);
    }

    public function destroy(Request $request, ApiKey $apiKey)
    {
        if (! Role::canManageTeam($request->attributes->get('membership')->role)) {
            abort(403);
        }
        $apiKey->update(['revoked_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function audit()
    {
        return response()->json(['audit' => AuditLog::query()->latest('id')->limit(200)->get()]);
    }
}
