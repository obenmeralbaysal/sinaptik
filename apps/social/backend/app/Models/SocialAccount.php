<?php

namespace App\Models;

use App\Casts\RotatableEncrypted;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'brand_id', 'platform', 'external_id', 'name', 'username',
        'parent_external_id', 'access_token', 'token_expires_at', 'scopes', 'status',
        'health_checked_at', 'health_error', 'meta', 'connected_by',
    ];

    protected $hidden = ['access_token'];

    protected function casts(): array
    {
        return [
            'access_token' => RotatableEncrypted::class,
            'token_expires_at' => 'datetime',
            'health_checked_at' => 'datetime',
            'scopes' => 'array',
            'meta' => 'array',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'platform' => $this->platform,
            'external_id' => $this->external_id,
            'name' => $this->name,
            'username' => $this->username,
            'parent_external_id' => $this->parent_external_id,
            'status' => $this->status,
            'token_expires_at' => $this->token_expires_at,
            'health_checked_at' => $this->health_checked_at,
            'health_error' => $this->health_error,
            'scopes' => $this->scopes,
        ];
    }
}
