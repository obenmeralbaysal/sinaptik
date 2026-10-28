<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class AdChangeLog extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'proposal_id', 'ad_set_id', 'external_id', 'field',
        'previous_value', 'new_value', 'applied_at', 'reverted_at', 'actor_type', 'actor_id',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'reverted_at' => 'datetime',
        ];
    }
}
