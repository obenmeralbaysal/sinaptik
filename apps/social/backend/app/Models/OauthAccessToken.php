<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OauthAccessToken extends Model
{
    protected $hidden = ['token_hash', 'refresh_token_hash'];

    protected $fillable = [
        'token_hash', 'refresh_token_hash', 'client_id', 'user_id', 'workspace_id',
        'scopes', 'expires_at', 'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
