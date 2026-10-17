<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use BelongsToWorkspace;

    public $timestamps = false;

    protected $fillable = [
        'workspace_id', 'actor_type', 'actor_id', 'action', 'tool', 'params_summary',
        'result', 'cost_minor', 'budget_delta_minor', 'ip', 'meta', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
