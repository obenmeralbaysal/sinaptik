<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'plan_id',
        'amount_cents',
        'currency',
        'provider',
        'status',
        'transaction_id',
        'raw_payload',
        'subscription_id',
        'event_id',
        'kind',
        'description',
        'gateway_payment_id',
        'meta',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'meta' => 'array',
            'paid_at' => 'datetime',
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
}
