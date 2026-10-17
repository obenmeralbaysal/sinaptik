<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function enable(Request $request, Google2FA $google2fa)
    {
        $secret = $google2fa->generateSecretKey();
        $request->user()->forceFill(['totp_secret' => $secret, 'totp_confirmed_at' => null])->save();
        $otpauth = $google2fa->getQRCodeUrl(config('app.name', 'Sinapp Social'), $request->user()->email, $secret);

        return response()->json(['secret' => $secret, 'otpauth' => $otpauth]);
    }

    public function confirm(Request $request, Google2FA $google2fa)
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();
        if (! $user->totp_secret || ! $google2fa->verifyKey($user->totp_secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'Doğrulama kodu geçersiz.']);
        }
        $user->forceFill(['totp_confirmed_at' => now()])->save();

        return response()->json(['ok' => true]);
    }

    public function disable(Request $request)
    {
        $data = $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Şifre hatalı.']);
        }
        $request->user()->forceFill(['totp_secret' => null, 'totp_confirmed_at' => null])->save();

        return response()->json(['ok' => true]);
    }

    public function challenge(Request $request, Google2FA $google2fa)
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $userId = $request->session()->get('2fa:user_id');
        $user = $userId ? User::query()->find($userId) : null;
        if (! $user || ! $user->totp_secret || ! $google2fa->verifyKey($user->totp_secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'Doğrulama kodu geçersiz.']);
        }
        $request->session()->forget('2fa:user_id');
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json(['user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'totp_enabled' => true,
        ]]);
    }
}
