<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Identity\Oidc\OidcException;
use App\Identity\OidcIdentityProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OidcController extends Controller
{
    public function login(OidcIdentityProvider $provider): RedirectResponse|JsonResponse
    {
        if (config('identity.driver') !== 'oidc') {
            abort(404);
        }

        try {
            return redirect()->away($provider->authorizationUrl());
        } catch (OidcException $e) {
            return $this->failure($e);
        }
    }

    public function callback(Request $request, OidcIdentityProvider $provider): RedirectResponse|JsonResponse
    {
        if (config('identity.driver') !== 'oidc') {
            abort(404);
        }

        if ($request->query('error') !== null) {
            return response()->json(['message' => 'Giriş tamamlanamadı.'], 400);
        }

        $code = $request->query('code');
        $state = $request->query('state');

        try {
            if (! is_string($code) || ! is_string($state)) {
                throw new OidcException('Oturum doğrulaması başarısız.');
            }
            $provider->completeLogin($code, $state);
        } catch (OidcException $e) {
            return $this->failure($e);
        }

        $request->session()->regenerate();

        return redirect()->away($provider->postLoginUrl());
    }

    private function failure(OidcException $e): JsonResponse
    {
        $status = $e->getCode();
        if (! in_array($status, [400, 502, 503], true)) {
            $status = 400;
        }

        return response()->json(['message' => $e->getMessage()], $status);
    }
}
