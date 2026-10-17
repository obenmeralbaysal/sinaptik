<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataDeletionRequest extends Model
{
    protected $fillable = ['confirmation_code', 'platform', 'status', 'external_user_id', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
