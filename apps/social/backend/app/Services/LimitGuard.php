<?php

namespace App\Services;

use App\Exceptions\LimitExceeded;
use App\Models\AdAccount;
use App\Models\AutomationRule;
use App\Models\Invitation;
use App\Models\MediaAsset;
use App\Models\Plan;
use App\Models\SocialAccount;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Models\WorkspaceAddon;
use App\Models\WorkspaceUser;

class LimitGuard
{
    public function __construct(private UsageMeter $usage) {}

    public function plan(Workspace $workspace): Plan
    {
        $subscription = $workspace->relationLoaded('subscription')
            ? $workspace->subscription
            : $workspace->subscription()->with('plan')->first();

        if (! $subscription instanceof Subscription || ! $subscription->plan) {
            $subscription = Subscription::query()->where('workspace_id', $workspace->id)->with('plan')->latest('id')->first();
        }

        return $subscription?->plan ?? Plan::query()->where('code', 'starter')->firstOrFail();
    }

    public function effective(Workspace $workspace, string $metric): ?int
    {
        $limit = $this->plan($workspace)->limit($metric);
        if ($limit === null) {
            return null;
        }

        $extra = match ($metric) {
            'seats' => $this->addonQuantity($workspace, 'extra_seat'),
            'social_accounts' => $this->addonQuantity($workspace, 'extra_account'),
            default => 0,
        };

        return $limit + $extra;
    }

    public function used(Workspace $workspace, string $metric): int
    {
        return match ($metric) {
            'social_accounts' => SocialAccount::query()
                ->where('workspace_id', $workspace->id)
                ->whereIn('platform', ['facebook_page', 'instagram'])
                ->where('status', '!=', 'disconnected')
                ->count(),
            'posts_per_month' => $this->usage->value($workspace, 'posts'),
            'seats' => WorkspaceUser::query()->where('workspace_id', $workspace->id)->count()
                + Invitation::query()->where('workspace_id', $workspace->id)
                    ->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now())->count(),
            'ad_accounts' => AdAccount::query()->where('workspace_id', $workspace->id)->count(),
            'automation_rules' => AutomationRule::query()->where('workspace_id', $workspace->id)->count(),
            'mcp_calls_per_month' => $this->usage->value($workspace, 'mcp_calls'),
            'storage_gb' => (int) ceil(MediaAsset::query()->where('workspace_id', $workspace->id)->sum('size_bytes') / 1_000_000_000),
            default => 0,
        };
    }

    public function assert(Workspace $workspace, string $metric, int $increment = 1): void
    {
        $limit = $this->effective($workspace, $metric);
        if ($limit === null) {
            return;
        }

        if ($this->used($workspace, $metric) + $increment > $limit) {
            throw new LimitExceeded($metric, $limit);
        }
    }

    public function snapshot(Workspace $workspace): array
    {
        $metrics = ['social_accounts', 'posts_per_month', 'seats', 'ad_accounts', 'automation_rules', 'mcp_calls_per_month', 'storage_gb'];
        $out = [];
        foreach ($metrics as $metric) {
            $out[$metric] = [
                'used' => $this->used($workspace, $metric),
                'limit' => $this->effective($workspace, $metric),
            ];
        }
        $out['mcp_calls_per_minute'] = $this->plan($workspace)->limit('mcp_calls_per_minute');
        $out['ads_auto_apply'] = $this->plan($workspace)->allows('ads_auto_apply');

        return $out;
    }

    private function addonQuantity(Workspace $workspace, string $code): int
    {
        return (int) WorkspaceAddon::query()->where('workspace_id', $workspace->id)->where('code', $code)->sum('quantity');
    }
}
