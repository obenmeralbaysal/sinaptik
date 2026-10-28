<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleSyncJob extends Model
{
    protected $fillable = [
        'workspace_id',
        'google_connection_id',
        'status',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(GoogleConnection::class, 'google_connection_id');
    }
}
