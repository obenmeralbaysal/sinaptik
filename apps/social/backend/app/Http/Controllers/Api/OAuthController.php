<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OauthAccessToken;
use App\Models\OauthAuthCode;
use App\Models\OauthClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    public function metadata()
    {
        $base = rtrim((string) config('app.url'), '/');

        return response()->json([
            'issuer' => $base,
            'authorization_endpoint' => $base.'/oauth/authorize',
            'token_endpoint' => $base.'/oauth/token',
            'registration_endpoint' => $base.'/oauth/register',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256'],
            'scopes_supported' => ['posts:read', 'posts:write', 'media:write', 'analytics:read', 'ads:read', 'ads:write', 'ads:budget', 'approvals:write'],
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:120'],
            'redirect_uris' => ['required', 'array', 'min:1'],
            'redirect_uris.*' => ['url'],
            'token_endpoint_auth_method' => ['nullable', 'in:none'],
        ]);
        $client = OauthClient::query()->create([
            'client_id' => 'soc_'.Str::random(24),
            'name' => $data['client_name'],
            'redirect_uris' => $data['redirect_uris'],
            'token_endpoint_auth_method' => $data['token_endpoint_auth_method'] ?? 'none',
        ]);

        return response()->json([
            'client_id' => $client->client_id,
            'client_name' => $client->name,
            'redirect_uris' => $client->redirect_uris,
            'token_endpoint_auth_method' => 'none',
        ], 201);
    }

    public function authorizeClient(Request $request)
    {
        $request->validate([
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'url'],
            'code_challenge' => ['required', 'string'],
            'code_challenge_method' => ['required', 'in:S256'],
            'state' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
            'workspace_id' => ['required', 'integer'],
        ]);
        if (! $request->user()) {
            return redirect(rtrim((string) config('sinsocial.frontend_url'), '/').'/giris?return='.urlencode($request->fullUrl()));
        }

        $client = OauthClient::query()->where('client_id', $request->string('client_id'))->firstOrFail();
        if (! in_array($request->string('redirect_uri'), $client->redirect_uris, true)) {
            abort(400, 'redirect_uri kayıtlı değil.');
        }

        $plain = Str::random(64);
        OauthAuthCode::query()->create([
            'code_hash' => hash('sha256', $plain),
            'client_id' => $client->client_id,
            'user_id' => $request->user()->id,
            'workspace_id' => (int) $request->integer('workspace_id'),
            'scopes' => array_values(array_filter(explode(' ', (string) $request->input('scope')))),
            'code_challenge' => $request->string('code_challenge'),
            'code_challenge_method' => 'S256',
            'redirect_uri' => $request->string('redirect_uri'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $url = $request->string('redirect_uri').(str_contains($request->string('redirect_uri'), '?') ? '&' : '?').http_build_query([
            'code' => $plain,
            'state' => $request->input('state'),
        ]);

        return redirect($url);
    }

    public function token(Request $request)
    {
        $data = $request->validate([
            'grant_type' => ['required', 'in:authorization_code,refresh_token'],
            'code' => ['required_if:grant_type,authorization_code'],
            'code_verifier' => ['required_if:grant_type,authorization_code'],
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required_if:grant_type,authorization_code'],
            'refresh_token' => ['required_if:grant_type,refresh_token'],
        ]);

        if ($data['grant_type'] === 'refresh_token') {
            $existing = OauthAccessToken::query()->where('refresh_token_hash', hash('sha256', $data['refresh_token']))->whereNull('revoked_at')->first();
            if (! $existing) {
                abort(400, 'refresh_token geçersiz.');
            }
            $existing->update(['revoked_at' => now()]);
            $issued = $this->issue($existing->client_id, $existing->user_id, $existing->workspace_id, $existing->scopes ?? []);

            return response()->json($issued);
        }

        $code = OauthAuthCode::query()->where('code_hash', hash('sha256', $data['code']))->first();
        if (! $code || $code->used_at || $code->expires_at->isPast() || $code->client_id !== $data['client_id'] || $code->redirect_uri !== $data['redirect_uri']) {
            abort(400, 'Yetki kodu geçersiz.');
        }
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $data['code_verifier'], true)), '+/', '-_'), '=');
        if (! hash_equals($code->code_challenge, $challenge)) {
            abort(400, 'PKCE doğrulanamadı.');
        }
        $code->update(['used_at' => now()]);

        return response()->json($this->issue($code->client_id, $code->user_id, $code->workspace_id, $code->scopes ?? []));
    }

    private function issue(string $clientId, int $userId, int $workspaceId, array $scopes): array
    {
        $access = 'soa_'.Str::random(48);
        $refresh = 'sor_'.Str::random(48);
        OauthAccessToken::query()->create([
            'token_hash' => hash('sha256', $access),
            'refresh_token_hash' => hash('sha256', $refresh),
            'client_id' => $clientId,
            'user_id' => $userId,
            'workspace_id' => $workspaceId,
            'scopes' => $scopes,
            'expires_at' => now()->addHour(),
        ]);

        return [
            'access_token' => $access,
            'refresh_token' => $refresh,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => implode(' ', $scopes),
        ];
    }
}
