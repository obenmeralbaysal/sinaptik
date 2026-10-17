<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdAccount extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'social_account_id', 'external_id', 'name', 'currency', 'status', 'meta',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class);
    }
}
