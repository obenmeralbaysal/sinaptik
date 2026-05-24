<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'plan_id',
        'amount_cents',
        'currency',
        'provider',
        'status',
        'transaction_id',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];
}
