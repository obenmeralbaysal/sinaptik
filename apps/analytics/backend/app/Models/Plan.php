<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'interval',
        'price_net_cents',
        'price_gross_cents',
        'currency',
        'site_limit',
        'features',
        'highlighted',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'highlighted' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
