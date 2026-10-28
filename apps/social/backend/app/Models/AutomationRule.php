<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['workspace_id', 'name', 'enabled', 'definition', 'last_ran_at'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'definition' => 'array',
            'last_ran_at' => 'datetime',
        ];
    }
}
