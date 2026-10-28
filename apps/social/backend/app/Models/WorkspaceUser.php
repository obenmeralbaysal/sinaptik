<?php

namespace App\Models;

use App\Support\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceUser extends Model
{
    protected $table = 'workspace_user';

    protected $fillable = ['workspace_id', 'user_id', 'role', 'ads_write'];

    protected function casts(): array
    {
        return ['ads_write' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function canWriteAds(): bool
    {
        return Role::canWriteAds($this->role, (bool) $this->ads_write);
    }
}
