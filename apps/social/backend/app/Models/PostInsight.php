<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class PostInsight extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['workspace_id', 'post_id', 'metrics', 'synced_at'];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'synced_at' => 'datetime',
        ];
    }
}
