<?php

namespace App\Identity;

use App\Identity\Oidc\OidcException;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class IdentityMirror
{
    public function upsert(string $issuer, string $subject, ?string $email, ?string $name, bool $emailVerified): User
    {
        $email = $this->email($email);
        $name = $this->name($name);

        return DB::transaction(function () use ($issuer, $subject, $email, $name, $emailVerified) {
            $existing = User::query()->where('external_subject_id', $subject)->first();
            if ($existing) {
                if ($existing->issuer !== $issuer) {
                    throw new OidcException('Kimlik eşlemesi çakışıyor.');
                }
                $this->refresh($existing, $email, $name, $emailVerified);

                return $existing;
            }

            try {
                return $this->create($issuer, $subject, $email, $name, $emailVerified);
            } catch (UniqueConstraintViolationException) {
                $again = User::query()->where('external_subject_id', $subject)->first();
                if ($again instanceof User && $again->issuer === $issuer) {
                    return $again;
                }
                if ($email === null) {
                    throw new OidcException('Kimlik eşlemesi çakışıyor.');
                }

                return $this->create($issuer, $subject, null, $name, false);
            }
        });
    }

    private function create(string $issuer, string $subject, ?string $email, ?string $name, bool $emailVerified): User
    {
        $user = new User([
            'external_subject_id' => $subject,
            'issuer' => $issuer,
            'name' => $name,
            'email' => $email,
        ]);
        if ($emailVerified && $email !== null) {
            $user->email_verified_at = now();
        }
        $user->save();

        $workspaceName = $name ?: 'Çalışma alanı';
        $workspace = Workspace::create([
            'slug' => $this->uniqueSlug($workspaceName),
            'name' => $workspaceName,
            'owner_id' => $user->id,
        ]);
        $workspace->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_workspace_id' => $workspace->id])->save();

        return $user;
    }

    private function refresh(User $user, ?string $email, ?string $name, bool $emailVerified): void
    {
        $dirty = [];
        if ($name !== null && $name !== $user->name) {
            $dirty['name'] = $name;
        }
        if ($email !== null && $email !== $user->email) {
            $taken = User::query()->where('email', $email)->whereKeyNot($user->id)->exists();
            if (! $taken) {
                $dirty['email'] = $email;
                if ($emailVerified) {
                    $dirty['email_verified_at'] = now();
                }
            }
        }
        if ($dirty !== []) {
            $user->forceFill($dirty)->save();
        }
    }

    private function email(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }
        $email = trim($email);
        if ($email === '' || strlen($email) > 191 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $email;
    }

    private function name(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        return mb_substr($name, 0, 120);
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
