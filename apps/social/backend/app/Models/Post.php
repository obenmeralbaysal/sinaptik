<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'brand_id', 'social_account_id', 'author_id', 'status', 'post_type',
        'body', 'media_ids', 'scheduled_at', 'published_at', 'external_id', 'failure_reason',
        'attempt_count', 'quota_counted', 'platform_options',
    ];

    protected function casts(): array
    {
        return [
            'media_ids' => 'array',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'quota_counted' => 'boolean',
            'platform_options' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PostApproval::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
