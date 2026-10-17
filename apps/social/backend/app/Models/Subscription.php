<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'plan_id', 'status', 'period', 'trial_ends_at',
        'current_period_start', 'current_period_end', 'pending_plan_id', 'pending_period',
        'cancel_at_period_end', 'gateway', 'gateway_subscription_id',
        'dunning_attempts', 'dunning_started_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'dunning_started_at' => 'datetime',
            'cancel_at_period_end' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function pendingPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'pending_plan_id');
    }
}
