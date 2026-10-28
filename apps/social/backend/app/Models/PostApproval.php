<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostApproval extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'post_id', 'requested_by', 'decided_by', 'status', 'comment',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
