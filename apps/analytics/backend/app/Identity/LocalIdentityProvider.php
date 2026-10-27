<?php

namespace App\Identity;

use App\Contracts\IdentityProvider;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                'email' => ['E-posta veya parola hatalı.'],
            ]);
        }

        $user = Auth::guard('web')->user();
        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'email' => ['E-posta veya parola hatalı.'],
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

    /**
     * @param  array{name: string, email: string, password: string, workspace_name?: string|null}  $attributes
     */
    public function register(array $attributes): User
    {
        return DB::transaction(function () use ($attributes) {
            $user = User::create([
                'external_subject_id' => (string) Str::uuid(),
                'issuer' => (string) config('identity.local_issuer', 'local'),
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
            ]);

            $workspaceName = $attributes['workspace_name'] ?? ($attributes['name'].' çalışma alanı');
            $workspace = Workspace::create([
                'slug' => $this->uniqueSlug($workspaceName),
                'name' => $workspaceName,
                'owner_id' => $user->id,
            ]);

            $workspace->users()->attach($user->id, ['role' => 'owner']);
            $user->forceFill(['current_workspace_id' => $workspace->id])->save();

            Auth::guard('web')->login($user);

            return $user->fresh(['workspaces', 'currentWorkspace']);
        });
    }

    private function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'calisma-alani';
        $candidate = $slug;
        $i = 1;
        while (Workspace::query()->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$i++;
        }

        return $candidate;
    }
}
