<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Workspace extends Model
{
    protected $fillable = [
        'name', 'slug', 'owner_id', 'timezone', 'locale', 'status', 'trial_ends_at',
        'read_only_at', 'trial_lapsed_at', 'deletion_scheduled_at', 'require_owner_2fa',
        'approval_required', 'ads_auto_apply', 'ads_kill_switch', 'ads_daily_cap_minor',
        'ads_monthly_cap_minor', 'ads_max_increase_percent', 'ads_cooldown_hours',
        'system_user_enabled', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'read_only_at' => 'datetime',
            'trial_lapsed_at' => 'datetime',
            'deletion_scheduled_at' => 'datetime',
            'require_owner_2fa' => 'boolean',
            'approval_required' => 'boolean',
            'ads_auto_apply' => 'boolean',
            'ads_kill_switch' => 'boolean',
            'system_user_enabled' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function isReadOnly(): bool
    {
        return in_array($this->status, ['read_only', 'pending_deletion'], true);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')->withPivot(['role', 'ads_write'])->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(WorkspaceUser::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
