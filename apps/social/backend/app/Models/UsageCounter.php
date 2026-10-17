<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class UsageCounter extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['workspace_id', 'metric', 'period', 'count'];
}
