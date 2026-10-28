<?php

namespace App\Identity;

use App\Contracts\IdentityProvider;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LocalIdentityProvider implements IdentityProvider
{
    public function currentUser(): ?User
    {
        $user = Auth::guard('web')->user();

        return $user instanceof User ? $user : null;
    }

    public function authenticate(array $credentials): User
    {
        $email = (string) ($credentials['email'] ?? '');
        $password = (string) ($credentials['password'] ?? '');
        $remember = (bool) ($credentials['remember'] ?? false);

        if (! Auth::guard('web')->attempt(['email' => $email, 'password' => $password], $remember)) {
            throw ValidationException::withMessages([
                'email' => ['E-posta veya şifre hatalı.'],
            ]);
        }

        $user = Auth::guard('web')->user();
        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'email' => ['E-posta veya şifre hatalı.'],
            ]);
        }

        return $user;
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();

        if (session()->isStarted()) {
            session()->invalidate();
            session()->regenerateToken();
        }
    }
}
