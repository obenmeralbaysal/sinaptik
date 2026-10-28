<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Invitation;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Services\InvitationService;
use App\Services\LimitGuard;
use App\Services\WorkspaceService;
use App\Support\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $workspaces = Workspace::query()
            ->whereIn('id', WorkspaceUser::query()->where('user_id', $request->user()->id)->pluck('workspace_id'))
            ->get();

        return response()->json(['workspaces' => $workspaces]);
    }

    public function store(Request $request, WorkspaceService $service)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $workspace = $service->create($request->user(), $data['name']);

        return response()->json(['workspace' => $workspace], 201);
    }

    public function show(Request $request, LimitGuard $limits)
    {
        return response()->json([
            'workspace' => $request->attributes->get('workspace'),
            'membership' => $request->attributes->get('membership'),
            'usage' => $limits->snapshot($request->attributes->get('workspace')),
        ]);
    }

    public function update(Request $request)
    {
        $workspace = $request->attributes->get('workspace');
        $membership = $request->attributes->get('membership');
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'timezone' => ['sometimes', 'string'],
            'locale' => ['sometimes', 'in:tr,en'],
            'approval_required' => ['sometimes', 'boolean'],
            'require_owner_2fa' => ['sometimes', 'boolean'],
        ]);
        if (array_key_exists('require_owner_2fa', $data) && $membership->role !== Role::OWNER) {
            abort(403);
        }
        if (! Role::canManageTeam($membership->role) && array_diff(array_keys($data), ['locale'])) {
            abort(403);
        }
        $workspace->update($data);

        return response()->json(['workspace' => $workspace->fresh()]);
    }

    public function switch(Request $request)
    {
        $workspace = $request->attributes->get('workspace');
        $request->user()->forceFill(['current_workspace_id' => $workspace->id])->save();

        return response()->json(['ok' => true]);
    }

    public function transfer(Request $request, WorkspaceService $service)
    {
        $data = $request->validate(['user_id' => ['required', 'integer']]);
        $workspace = $service->transfer($request->attributes->get('workspace'), $request->user(), (int) $data['user_id']);

        return response()->json(['workspace' => $workspace]);
    }

    public function destroy(Request $request, WorkspaceService $service)
    {
        $workspace = $service->scheduleDeletion($request->attributes->get('workspace'), $request->user());

        return response()->json(['workspace' => $workspace]);
    }

    public function cancelDeletion(Request $request, WorkspaceService $service)
    {
        $workspace = $service->cancelDeletion($request->attributes->get('workspace'), $request->user());

        return response()->json(['workspace' => $workspace]);
    }

    public function adsSettings(Request $request, LimitGuard $limits)
    {
        $membership = $request->attributes->get('membership');
        if ($membership->role !== Role::OWNER) {
            abort(403, 'Otomatik reklam modunu yalnızca sahip açabilir.');
        }
        $workspace = $request->attributes->get('workspace');
        $data = $request->validate([
            'ads_auto_apply' => ['sometimes', 'boolean'],
            'ads_kill_switch' => ['sometimes', 'boolean'],
            'ads_daily_cap_minor' => ['sometimes', 'integer', 'min:0'],
            'ads_monthly_cap_minor' => ['sometimes', 'integer', 'min:0'],
            'ads_max_increase_percent' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'ads_cooldown_hours' => ['sometimes', 'integer', 'min:1', 'max:168'],
        ]);
        if (($data['ads_auto_apply'] ?? false) && ! $limits->plan($workspace)->allows('ads_auto_apply')) {
            abort(422, 'Bu planda otomatik uygulama yok.');
        }
        $workspace->update($data);

        return response()->json(['workspace' => $workspace->fresh()]);
    }

    public function members(Request $request)
    {
        $members = WorkspaceUser::query()
            ->where('workspace_id', $request->attributes->get('workspace')->id)
            ->with('user:id,name,email')
            ->get();

        return response()->json(['members' => $members]);
    }

    public function updateMember(Request $request, int $userId)
    {
        $this->assertTeam($request);
        $data = $request->validate([
            'role' => ['sometimes', Rule::in(Role::ALL)],
            'ads_write' => ['sometimes', 'boolean'],
        ]);
        if (($data['role'] ?? null) === Role::OWNER) {
            abort(422, 'Sahip rolü devir ile verilir.');
        }
        $membership = WorkspaceUser::query()
            ->where('workspace_id', $request->attributes->get('workspace')->id)
            ->where('user_id', $userId)
            ->firstOrFail();
        if ($membership->role === Role::OWNER) {
            abort(422, 'Sahip rolü buradan değiştirilemez.');
        }
        $membership->update($data);

        return response()->json(['member' => $membership]);
    }

    public function removeMember(Request $request, int $userId)
    {
        $this->assertTeam($request);
        $membership = WorkspaceUser::query()
            ->where('workspace_id', $request->attributes->get('workspace')->id)
            ->where('user_id', $userId)
            ->firstOrFail();
        if ($membership->role === Role::OWNER) {
            abort(422);
        }
        $membership->delete();

        return response()->json(['ok' => true]);
    }

    public function invites(Request $request)
    {
        return response()->json([
            'invites' => Invitation::query()->whereNull('accepted_at')->latest('id')->get()->makeHidden('token_hash'),
        ]);
    }

    public function invite(Request $request, InvitationService $service)
    {
        $this->assertTeam($request);
        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', Rule::in([Role::ADMIN, Role::EDITOR, Role::APPROVER, Role::VIEWER])],
            'ads_write' => ['sometimes', 'boolean'],
            'brand_id' => ['nullable', 'integer'],
        ]);
        $invitation = $service->invite(
            $request->attributes->get('workspace'),
            $request->user(),
            $data['email'],
            $data['role'],
            (bool) ($data['ads_write'] ?? false),
            $data['brand_id'] ?? null,
        );

        return response()->json(['invite' => $invitation->makeHidden('token_hash'), 'token' => $invitation->plain_token], 201);
    }

    public function revokeInvite(Request $request, Invitation $invitation, InvitationService $service)
    {
        $this->assertTeam($request);
        abort_unless($invitation->workspace_id === $request->attributes->get('workspace')->id, 404);
        $service->revoke($invitation);

        return response()->json(['ok' => true]);
    }

    public function acceptInvite(Request $request, InvitationService $service)
    {
        $data = $request->validate(['token' => ['required', 'string']]);
        $membership = $service->accept($request->user(), $data['token']);

        return response()->json(['membership' => $membership]);
    }

    public function brands(Request $request)
    {
        return response()->json(['brands' => Brand::query()->orderBy('name')->get()]);
    }

    public function storeBrand(Request $request)
    {
        $this->assertTeam($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $base = Str::slug($data['name']) ?: 'marka';
        $slug = $base;
        $i = 2;
        while (Brand::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        $brand = Brand::query()->create(['name' => $data['name'], 'slug' => $slug]);

        return response()->json(['brand' => $brand], 201);
    }

    private function assertTeam(Request $request): void
    {
        if (! Role::canManageTeam($request->attributes->get('membership')->role)) {
            abort(403);
        }
    }
}
