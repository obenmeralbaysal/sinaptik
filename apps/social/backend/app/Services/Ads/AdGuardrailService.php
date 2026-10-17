<?php

namespace App\Services\Ads;

use App\Models\AdChangeLog;
use App\Models\AdSet;
use App\Models\Workspace;

class AdGuardrailService
{
    public function check(Workspace $workspace, ?AdSet $adSet, string $kind, array $payload, bool $isRevert = false): array
    {
        $reasons = [];

        if ($workspace->ads_kill_switch) {
            $reasons[] = 'kill_switch';
        }

        if ($kind === 'budget' && $adSet) {
            $old = (int) ($adSet->daily_budget_minor ?? 0);
            $new = (int) ($payload['daily_budget_minor'] ?? 0);
            if ($new > $old && $old > 0) {
                $percent = (($new - $old) / $old) * 100;
                if ($percent > (float) $workspace->ads_max_increase_percent + 0.001) {
                    $reasons[] = 'max_increase';
                }
            }
            if ($new > $old) {
                $delta = $new - $old;
                if ($this->increasesSince($workspace, now()->startOfDay()) + $delta > (int) $workspace->ads_daily_cap_minor) {
                    $reasons[] = 'daily_cap';
                }
                if ($this->increasesSince($workspace, now()->startOfMonth()) + $delta > (int) $workspace->ads_monthly_cap_minor) {
                    $reasons[] = 'monthly_cap';
                }
            }
        }

        if (! $isRevert && $adSet && $this->inCooldown($workspace, $adSet)) {
            $reasons[] = 'cooldown';
        }

        return ['ok' => $reasons === [], 'reasons' => array_values(array_unique($reasons))];
    }

    public function inCooldown(Workspace $workspace, AdSet $adSet): bool
    {
        return AdChangeLog::query()
            ->where('ad_set_id', $adSet->id)
            ->whereNull('reverted_at')
            ->where('applied_at', '>=', now()->subHours((int) $workspace->ads_cooldown_hours))
            ->exists();
    }

    public function increasesSince(Workspace $workspace, $since): int
    {
        return (int) AdChangeLog::query()
            ->where('workspace_id', $workspace->id)
            ->where('field', 'daily_budget_minor')
            ->whereNull('reverted_at')
            ->where('applied_at', '>=', $since)
            ->get()
            ->sum(function (AdChangeLog $log) {
                return max(0, (int) $log->new_value - (int) $log->previous_value);
            });
    }
}
