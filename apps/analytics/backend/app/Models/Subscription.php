<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'plan_id',
        'provider_subscription_id',
        'status',
        'period_end',
    ];

    protected function casts(): array
    {
        return [
            'period_end' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function scopeCurrentlyActive(Builder $query): void
    {
        $query->where(function (Builder $query) {
            $query->where(function (Builder $query) {
                $query->where('status', 'active')
                    ->where(function (Builder $query) {
                        $query->whereNull('period_end')->orWhere('period_end', '>', now());
                    });
            })->orWhere(function (Builder $query) {
                $query->where('status', 'canceled_until')->where('period_end', '>', now());
            });
        });
    }
}
