<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Services\X\XOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialAccountController extends Controller
{
    public function index(): JsonResponse
    {
        $accounts = SocialAccount::query()->orderByDesc('created_at')->get();

        return response()->json([
            'data' => $accounts->map(fn ($a) => $this->present($a)),
        ]);
    }

    public function show(SocialAccount $socialAccount): JsonResponse
    {
        return response()->json(['data' => $this->present($socialAccount)]);
    }

    public function destroy(SocialAccount $socialAccount): JsonResponse
    {
        $socialAccount->delete();
        return response()->json(['message' => 'Disconnected.']);
    }

    public function xConnect(Request $request, XOAuthService $oauth): JsonResponse
    {
        $org = app('currentOrganization');
        return response()->json([
            'auth_url' => $oauth->getAuthUrl($org),
        ]);
    }

    public function xCallback(Request $request, XOAuthService $oauth)
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');

        $frontend = rtrim((string) config('app.frontend_url'), '/');

        if ($error || ! $code || ! $state) {
            return redirect()->away($frontend.'/?x_error='.urlencode($error ?? 'missing_code'));
        }

        try {
            $account = $oauth->exchangeCode($code, $state);
        } catch (\Throwable $e) {
            return redirect()->away($frontend.'/?x_error='.urlencode($e->getMessage()));
        }

        $orgSlug = $account->organization()->withoutGlobalScopes()->first()->slug;

        return redirect()->away($frontend."/t/{$orgSlug}/accounts?connected=1");
    }

    private function present(SocialAccount $a): array
    {
        return [
            'id' => $a->id,
            'provider' => $a->provider,
            'username' => $a->username,
            'display_name' => $a->display_name,
            'avatar_url' => $a->avatar_url,
            'is_active' => $a->is_active,
            'expires_at' => $a->expires_at,
            'reply_frequency_minutes' => $a->reply_frequency_minutes,
            'daily_reply_limit' => $a->daily_reply_limit,
            'ai_model' => $a->ai_model,
            'created_at' => $a->created_at,
        ];
    }
}
