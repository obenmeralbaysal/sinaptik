<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class AccountInsight extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['workspace_id', 'social_account_id', 'metric_date', 'metrics'];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'metrics' => 'array',
        ];
    }
}
