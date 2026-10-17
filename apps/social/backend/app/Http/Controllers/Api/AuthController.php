<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WorkspaceService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request, WorkspaceService $workspaces)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', PasswordRule::defaults()],
            'workspace_name' => ['nullable', 'string', 'max:120'],
            'locale' => ['nullable', 'in:tr,en'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'locale' => $data['locale'] ?? 'tr',
        ]);
        $user->sendEmailVerificationNotification();

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $workspace = null;
        if ($user->hasVerifiedEmail() && ! empty($data['workspace_name'])) {
            $workspace = $workspaces->create($user, $data['workspace_name']);
        }

        return response()->json([
            'user' => $this->userPayload($user),
            'workspace' => $workspace,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->attempt($data, true)) {
            throw ValidationException::withMessages(['email' => 'E-posta veya şifre hatalı.']);
        }

        $user = Auth::guard('web')->user();
        if ($user->totp_confirmed_at) {
            Auth::guard('web')->logout();
            $request->session()->put('2fa:user_id', $user->id);

            return response()->json(['two_factor' => true]);
        }

        $request->session()->regenerate();

        return response()->json(['user' => $this->userPayload($user->fresh())]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['ok' => true]);
        }
        $request->user()->sendEmailVerificationNotification();

        return response()->json(['ok' => true]);
    }

    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::query()->findOrFail($id);
        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect(rtrim((string) config('sinsocial.frontend_url'), '/').'/dogrula?status=ok');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        return response()->json(['ok' => true]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', PasswordRule::defaults()],
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password)])->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return response()->json(['ok' => true]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $user->locale,
            'email_verified_at' => $user->email_verified_at,
            'totp_enabled' => $user->totp_confirmed_at !== null,
            'current_workspace_id' => $user->current_workspace_id,
            'is_super_admin' => (bool) $user->is_super_admin,
        ];
    }
}
