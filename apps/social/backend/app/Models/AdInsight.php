<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class AdInsight extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['workspace_id', 'level', 'subject_id', 'metric_date', 'metrics'];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'metrics' => 'array',
        ];
    }
}
