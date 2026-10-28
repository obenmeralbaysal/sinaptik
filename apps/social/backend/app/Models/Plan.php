<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'price_monthly_minor', 'price_yearly_minor',
        'currency', 'limits', 'features', 'is_public', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'limits' => 'array',
            'features' => 'array',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function limit(string $key): ?int
    {
        $limits = $this->limits ?? [];
        if (! array_key_exists($key, $limits) || $limits[$key] === null) {
            return null;
        }

        return (int) $limits[$key];
    }

    public function allows(string $key): bool
    {
        $limits = $this->limits ?? [];

        return (bool) ($limits[$key] ?? false);
    }

    public function priceFor(string $period): int
    {
        return $period === 'yearly' ? (int) $this->price_yearly_minor : (int) $this->price_monthly_minor;
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
