<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialComment extends Model
{
    use BelongsToWorkspace;
    use SoftDeletes;

    protected $fillable = [
        'workspace_id', 'social_account_id', 'post_id', 'platform', 'external_id',
        'parent_external_id', 'author_external_id', 'author_username', 'body',
        'posted_at', 'hidden_at', 'purged_at', 'raw', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'hidden_at' => 'datetime',
            'purged_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'raw' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'social_account_id' => $this->social_account_id,
            'platform' => $this->platform,
            'external_id' => $this->external_id,
            'parent_external_id' => $this->parent_external_id,
            'author_username' => $this->author_username,
            'body' => $this->body,
            'posted_at' => $this->posted_at,
            'hidden_at' => $this->hidden_at,
            'last_synced_at' => $this->last_synced_at,
        ];
    }
}
