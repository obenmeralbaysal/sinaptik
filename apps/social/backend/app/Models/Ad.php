<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['workspace_id', 'ad_set_id', 'external_id', 'name', 'status', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }
}
