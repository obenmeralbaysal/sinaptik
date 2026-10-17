<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class WorkspaceAddon extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['workspace_id', 'code', 'quantity', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }
}
