<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdCampaign extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'ad_account_id', 'external_id', 'name', 'status', 'objective',
        'daily_budget_minor', 'lifetime_budget_minor', 'meta',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function adSets(): HasMany
    {
        return $this->hasMany(AdSet::class);
    }
}
