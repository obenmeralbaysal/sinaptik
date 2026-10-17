<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OauthAuthCode extends Model
{
    protected $fillable = [
        'code_hash', 'client_id', 'user_id', 'workspace_id', 'scopes', 'code_challenge',
        'code_challenge_method', 'redirect_uri', 'expires_at', 'used_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
