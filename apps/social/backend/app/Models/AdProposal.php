<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdProposal extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'ad_account_id', 'ad_campaign_id', 'ad_set_id', 'kind', 'status',
        'payload', 'guardrail', 'source', 'proposed_by', 'api_key_id', 'decided_by',
        'decided_at', 'applied_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'guardrail' => 'array',
            'decided_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function adSet(): BelongsTo
    {
        return $this->belongsTo(AdSet::class);
    }
}
