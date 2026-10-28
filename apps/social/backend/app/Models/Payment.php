<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'subscription_id', 'event_id', 'kind', 'status',
        'amount_minor', 'currency', 'description', 'gateway_payment_id', 'meta', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'paid_at' => 'datetime',
        ];
    }
}
