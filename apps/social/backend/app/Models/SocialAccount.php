<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SocialAccount extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'provider',
        'provider_user_id',
        'username',
        'display_name',
        'avatar_url',
        'access_token',
        'refresh_token',
        'expires_at',
        'scopes',
        'is_active',
        'ai_model',
        'reply_frequency_minutes',
        'daily_reply_limit',
        'last_reply_check_at',
        'settings',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'expires_at' => 'datetime',
        'last_reply_check_at' => 'datetime',
    ];

    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    public function isExpired(int $skewSeconds = 60): bool
    {
        if (! $this->expires_at) {
            return false;
        }
        return $this->expires_at->subSeconds($skewSeconds)->isPast();
    }
}
