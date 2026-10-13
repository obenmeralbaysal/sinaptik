<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Routine extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'social_account_id',
        'name',
        'persona',
        'tone',
        'language',
        'topic',
        'ai_model',
        'schedule_cron',
        'frequency_days',
        'window_start',
        'window_end',
        'timezone',
        'schedule_anchor',
        'daily_count',
        'generate_images',
        'image_model',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'generate_images' => 'boolean',
        'frequency_days' => 'integer',
        'schedule_anchor' => 'date:Y-m-d',
    ];

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
