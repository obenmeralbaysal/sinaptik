<?php

namespace App\Jobs;

use App\Jobs\Middleware\SetWorkspaceContext;
use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdInsight;
use App\Models\AdSet;
use App\Models\SocialAccount;
use App\Services\ConsentService;
use App\Services\Meta\MetaDriver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncAds implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $adAccountId, public int $workspaceId)
    {
        $this->onQueue('ads-sync');
    }

    public function middleware(): array
    {
        return [new SetWorkspaceContext($this->workspaceId)];
    }

    public function handle(MetaDriver $meta, ConsentService $consents): void
    {
        if (! $consents->allowsSync($this->workspaceId)) {
            return;
        }

        $adAccount = AdAccount::query()->find($this->adAccountId);
        if (! $adAccount) {
            return;
        }
        $social = SocialAccount::query()->find($adAccount->social_account_id);
        $token = (string) ($social->access_token ?? '');
        $data = $meta->syncAds($token, $adAccount->external_id);

        $campaignIds = [];
        foreach ($data['campaigns'] as $row) {
            $campaign = AdCampaign::query()->updateOrCreate(
                ['workspace_id' => $adAccount->workspace_id, 'external_id' => (string) $row['id']],
                [
                    'ad_account_id' => $adAccount->id,
                    'name' => $row['name'] ?? 'Kampanya',
                    'status' => $row['status'] ?? null,
                    'objective' => $row['objective'] ?? null,
                    'daily_budget_minor' => $row['daily_budget'] ?? null,
                ],
            );
            $campaignIds[(string) $row['id']] = $campaign->id;
        }

        $adSetIds = [];
        foreach ($data['adsets'] as $row) {
            $campaignId = $campaignIds[(string) ($row['campaign_id'] ?? '')] ?? AdCampaign::query()->where('external_id', $row['campaign_id'] ?? '')->value('id');
            if (! $campaignId) {
                continue;
            }
            $adSet = AdSet::query()->updateOrCreate(
                ['workspace_id' => $adAccount->workspace_id, 'external_id' => (string) $row['id']],
                [
                    'ad_campaign_id' => $campaignId,
                    'name' => $row['name'] ?? 'Reklam seti',
                    'status' => $row['status'] ?? null,
                    'daily_budget_minor' => $row['daily_budget'] ?? null,
                ],
            );
            $adSetIds[(string) $row['id']] = $adSet->id;
        }

        foreach ($data['ads'] as $row) {
            $adSetId = $adSetIds[(string) ($row['adset_id'] ?? '')] ?? null;
            if (! $adSetId) {
                continue;
            }
            Ad::query()->updateOrCreate(
                ['workspace_id' => $adAccount->workspace_id, 'external_id' => (string) $row['id']],
                ['ad_set_id' => $adSetId, 'name' => $row['name'] ?? 'Reklam', 'status' => $row['status'] ?? null],
            );
        }

        foreach ($data['insights'] as $row) {
            $external = (string) ($row['external_id'] ?? $row['adset_id'] ?? '');
            $subject = $adSetIds[$external] ?? null;
            if (! $subject) {
                continue;
            }
            AdInsight::query()->updateOrCreate(
                ['level' => 'adset', 'subject_id' => $subject, 'metric_date' => now()->toDateString()],
                ['workspace_id' => $adAccount->workspace_id, 'metrics' => $row['metrics'] ?? $row],
            );
        }
    }
}
