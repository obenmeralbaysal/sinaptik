<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdChangeLog;
use App\Models\AdInsight;
use App\Models\AdProposal;
use App\Models\AdSet;
use App\Models\AutomationRule;
use App\Services\Ads\AdProposalService;
use App\Services\LimitGuard;
use App\Support\Role;
use Illuminate\Http\Request;

class AdsController extends Controller
{
    public function accounts()
    {
        return response()->json(['ad_accounts' => AdAccount::query()->get()]);
    }

    public function campaigns()
    {
        return response()->json([
            'campaigns' => AdCampaign::query()->with('adSets')->get(),
            'insights' => AdInsight::query()->latest('metric_date')->limit(90)->get(),
        ]);
    }

    public function proposals()
    {
        return response()->json(['proposals' => AdProposal::query()->latest('id')->limit(100)->get()]);
    }

    public function propose(Request $request, AdProposalService $service)
    {
        $this->assertAds($request);
        $data = $request->validate([
            'ad_set_id' => ['required', 'integer'],
            'kind' => ['required', 'in:budget,status'],
            'daily_budget_minor' => ['required_if:kind,budget', 'integer', 'min:0'],
            'status' => ['required_if:kind,status', 'in:ACTIVE,PAUSED'],
            'auto' => ['sometimes', 'boolean'],
        ]);
        $adSet = AdSet::query()->findOrFail($data['ad_set_id']);
        $payload = $data['kind'] === 'budget'
            ? ['daily_budget_minor' => (int) $data['daily_budget_minor']]
            : ['status' => $data['status']];
        $proposal = $service->propose(
            $request->attributes->get('workspace'),
            $adSet,
            $data['kind'],
            $payload,
            'user',
            $request->user(),
            null,
            (bool) ($data['auto'] ?? false),
        );

        return response()->json(['proposal' => $proposal], 201);
    }

    public function approve(Request $request, AdProposal $proposal, AdProposalService $service)
    {
        $this->assertAds($request);

        return response()->json(['proposal' => $service->decide($proposal, $request->user(), 'approved')]);
    }

    public function reject(Request $request, AdProposal $proposal, AdProposalService $service)
    {
        $this->assertAds($request);

        return response()->json(['proposal' => $service->decide($proposal, $request->user(), 'rejected')]);
    }

    public function apply(Request $request, AdProposal $proposal, AdProposalService $service)
    {
        $this->assertAds($request);
        if (! in_array($proposal->status, ['approved', 'proposed'], true)) {
            abort(422, 'Bu öneri uygulanamaz.');
        }
        if ($proposal->status === 'proposed' && $request->attributes->get('membership')->role === Role::VIEWER) {
            abort(403);
        }

        return response()->json([
            'proposal' => $service->apply($request->attributes->get('workspace'), $proposal, $request->user()),
        ]);
    }

    public function changes()
    {
        return response()->json(['changes' => AdChangeLog::query()->latest('id')->limit(100)->get()]);
    }

    public function revert(Request $request, AdChangeLog $change, AdProposalService $service)
    {
        $this->assertAds($request);
        if (! in_array($request->attributes->get('membership')->role, [Role::OWNER, Role::ADMIN], true)) {
            abort(403);
        }

        return response()->json([
            'change' => $service->revert($request->attributes->get('workspace'), $change, $request->user()),
        ]);
    }

    public function rules(Request $request)
    {
        return response()->json(['rules' => AutomationRule::query()->get()]);
    }

    public function storeRule(Request $request, LimitGuard $limits)
    {
        $this->assertAds($request);
        $workspace = $request->attributes->get('workspace');
        $limits->assert($workspace, 'automation_rules');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'definition' => ['required', 'array'],
            'enabled' => ['sometimes', 'boolean'],
        ]);
        $rule = AutomationRule::query()->create([
            'name' => $data['name'],
            'definition' => $data['definition'],
            'enabled' => $data['enabled'] ?? true,
        ]);

        return response()->json(['rule' => $rule], 201);
    }

    private function assertAds(Request $request): void
    {
        if (! $request->attributes->get('membership')->canWriteAds()) {
            abort(403, 'Reklam yazma izni yok.');
        }
    }
}
