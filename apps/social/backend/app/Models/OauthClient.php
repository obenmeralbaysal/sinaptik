<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OauthClient extends Model
{
    protected $fillable = ['client_id', 'name', 'redirect_uris', 'workspace_id', 'token_endpoint_auth_method'];

    protected function casts(): array
    {
        return ['redirect_uris' => 'array'];
    }
}
