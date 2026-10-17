<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class CreditLedgerEntry extends Model
{
    use BelongsToWorkspace;

    public $timestamps = false;

    protected $table = 'credit_ledger';

    protected $fillable = [
        'workspace_id', 'currency_type', 'delta', 'balance_after', 'reason', 'reference', 'meta', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
