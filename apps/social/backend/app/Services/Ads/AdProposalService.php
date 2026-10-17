<?php

namespace App\Services\Ads;

use App\Exceptions\GuardrailException;
use App\Models\AdChangeLog;
use App\Models\AdProposal;
use App\Models\AdSet;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AuditLogger;
use App\Services\LimitGuard;
use App\Services\Meta\MetaDriver;

class AdProposalService
{
    public function __construct(
        private AdGuardrailService $guardrails,
        private MetaDriver $meta,
        private LimitGuard $limits,
        private AuditLogger $audit,
    ) {}

    public function propose(Workspace $workspace, AdSet $adSet, string $kind, array $payload, string $source, ?User $user, ?int $apiKeyId, bool $auto = false): AdProposal
    {
        $check = $this->guardrails->check($workspace, $adSet, $kind, $payload);
        if (! $check['ok']) {
            throw new GuardrailException($check['reasons']);
        }

        $proposal = AdProposal::query()->create([
            'workspace_id' => $workspace->id,
            'ad_account_id' => $adSet->campaign?->ad_account_id,
            'ad_campaign_id' => $adSet->ad_campaign_id,
            'ad_set_id' => $adSet->id,
            'kind' => $kind,
            'status' => 'proposed',
            'payload' => $payload,
            'guardrail' => $check,
            'source' => $source,
            'proposed_by' => $user?->id,
            'api_key_id' => $apiKeyId,
        ]);

        if ($auto && $this->autoApplyAllowed($workspace)) {
            return $this->apply($workspace, $proposal, $user, 'system');
        }

        return $proposal;
    }

    public function decide(AdProposal $proposal, User $user, string $status): AdProposal
    {
        $proposal->update([
            'status' => $status,
            'decided_by' => $user->id,
            'decided_at' => now(),
        ]);

        return $proposal->fresh();
    }

    public function apply(Workspace $workspace, AdProposal $proposal, ?User $user, string $actorType = 'user'): AdProposal
    {
        $adSet = $proposal->adSet()->firstOrFail();
        $check = $this->guardrails->check($workspace, $adSet, $proposal->kind, $proposal->payload);
        if (! $check['ok']) {
            $proposal->update(['status' => 'failed', 'failure_reason' => implode(',', $check['reasons']), 'guardrail' => $check]);
            throw new GuardrailException($check['reasons']);
        }

        $token = $this->tokenFor($adSet);
        $previous = null;
        $field = $proposal->kind === 'budget' ? 'daily_budget_minor' : 'status';
        $newValue = $proposal->kind === 'budget'
            ? (string) ($proposal->payload['daily_budget_minor'] ?? '')
            : (string) ($proposal->payload['status'] ?? '');
        $previous = $proposal->kind === 'budget'
            ? (string) ($adSet->daily_budget_minor ?? '')
            : (string) ($adSet->status ?? '');

        if ($proposal->kind === 'budget') {
            $this->meta->updateBudget($token, $adSet->external_id, (int) $newValue);
            $adSet->update(['daily_budget_minor' => (int) $newValue]);
        } else {
            $this->meta->updateStatus($token, $adSet->external_id, $newValue);
            $adSet->update(['status' => $newValue]);
        }

        $delta = $proposal->kind === 'budget' ? ((int) $newValue - (int) $previous) : 0;

        AdChangeLog::query()->create([
            'workspace_id' => $workspace->id,
            'proposal_id' => $proposal->id,
            'ad_set_id' => $adSet->id,
            'external_id' => $adSet->external_id,
            'field' => $field,
            'previous_value' => $previous,
            'new_value' => $newValue,
            'applied_at' => now(),
            'actor_type' => $actorType,
            'actor_id' => $user?->id,
        ]);

        $proposal->update([
            'status' => 'applied',
            'applied_at' => now(),
            'decided_by' => $proposal->decided_by ?: $user?->id,
            'decided_at' => $proposal->decided_at ?: now(),
            'guardrail' => $check,
        ]);

        $this->audit->log(
            $workspace->id,
            $actorType,
            $user?->id,
            'ads.apply',
            'ok',
            $proposal->kind,
            json_encode(['ad_set' => $adSet->id, 'from' => $previous, 'to' => $newValue]),
            null,
            $delta,
        );

        return $proposal->fresh();
    }

    public function revert(Workspace $workspace, AdChangeLog $log, ?User $user): AdChangeLog
    {
        if ($workspace->ads_kill_switch) {
            throw new GuardrailException(['kill_switch']);
        }
        if ($log->reverted_at) {
            abort(422, 'Bu değişiklik zaten geri alındı.');
        }

        $adSet = AdSet::query()->findOrFail($log->ad_set_id);
        $token = $this->tokenFor($adSet);
        if ($log->field === 'daily_budget_minor') {
            $this->meta->updateBudget($token, (string) $log->external_id, (int) $log->previous_value);
            $adSet->update(['daily_budget_minor' => (int) $log->previous_value]);
        } else {
            $this->meta->updateStatus($token, (string) $log->external_id, (string) $log->previous_value);
            $adSet->update(['status' => $log->previous_value]);
        }

        $log->update(['reverted_at' => now()]);

        AdChangeLog::query()->create([
            'workspace_id' => $workspace->id,
            'proposal_id' => $log->proposal_id,
            'ad_set_id' => $adSet->id,
            'external_id' => $log->external_id,
            'field' => $log->field,
            'previous_value' => $log->new_value,
            'new_value' => $log->previous_value,
            'applied_at' => now(),
            'actor_type' => 'user',
            'actor_id' => $user?->id,
        ]);

        return $log->fresh();
    }

    public function autoApplyAllowed(Workspace $workspace): bool
    {
        return $workspace->ads_auto_apply && $this->limits->plan($workspace)->allows('ads_auto_apply');
    }

    private function tokenFor(AdSet $adSet): string
    {
        $adSet->loadMissing('campaign.adAccount');
        $socialId = $adSet->campaign?->adAccount?->social_account_id;
        $account = $socialId ? SocialAccount::query()->find($socialId) : null;
        if (! $account?->access_token) {
            $account = SocialAccount::query()
                ->whereIn('platform', ['meta_user', 'meta_system_user', 'meta_ad_account'])
                ->where('status', 'connected')
                ->latest('id')
                ->first();
        }

        return (string) ($account->access_token ?? 'fake');
    }
}
