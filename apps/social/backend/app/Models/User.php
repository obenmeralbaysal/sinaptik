<?php

namespace App\Models;

use App\Casts\RotatableEncrypted;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'locale', 'current_workspace_id', 'totp_secret', 'is_super_admin', 'external_subject_id', 'issuer'])]
#[Hidden(['password', 'remember_token', 'totp_secret'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'totp_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'totp_secret' => RotatableEncrypted::class,
            'is_super_admin' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')->withPivot(['role', 'ads_write'])->withTimestamps();
    }
}
