<?php

namespace App\Services;

use App\Models\UsageCounter;
use App\Models\Workspace;

class UsageMeter
{
    public function increment(Workspace $workspace, string $metric, int $by = 1): int
    {
        $period = now()->format('Y-m');
        $counter = UsageCounter::query()->firstOrCreate(
            ['workspace_id' => $workspace->id, 'metric' => $metric, 'period' => $period],
            ['count' => 0],
        );
        $counter->increment('count', $by);

        return (int) $counter->fresh()->count;
    }

    public function value(Workspace $workspace, string $metric, ?string $period = null): int
    {
        return (int) UsageCounter::query()
            ->where('workspace_id', $workspace->id)
            ->where('metric', $metric)
            ->where('period', $period ?? now()->format('Y-m'))
            ->value('count');
    }
}
