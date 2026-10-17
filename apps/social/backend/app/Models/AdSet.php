<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdSet extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'ad_campaign_id', 'external_id', 'name', 'status',
        'daily_budget_minor', 'lifetime_budget_minor', 'meta',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }
}
