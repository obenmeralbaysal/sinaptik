<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Support\Role;
use App\Support\WorkspaceContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkspaceService
{
    public function create(User $owner, string $name): Workspace
    {
        return DB::transaction(function () use ($owner, $name) {
            $workspace = Workspace::query()->create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'owner_id' => $owner->id,
                'trial_ends_at' => now()->addDays((int) config('sinsocial.trial_days')),
            ]);

            WorkspaceUser::query()->create([
                'workspace_id' => $workspace->id,
                'user_id' => $owner->id,
                'role' => Role::OWNER,
                'ads_write' => true,
            ]);

            $owner->forceFill(['current_workspace_id' => $workspace->id])->save();
            app(WorkspaceContext::class)->activate($workspace->id);

            Brand::query()->create([
                'workspace_id' => $workspace->id,
                'name' => $name,
                'slug' => 'ana',
            ]);

            $plan = Plan::query()->where('code', config('sinsocial.trial_plan'))->firstOrFail();
            Subscription::query()->create([
                'workspace_id' => $workspace->id,
                'plan_id' => $plan->id,
                'status' => 'trialing',
                'period' => 'monthly',
                'trial_ends_at' => $workspace->trial_ends_at,
                'current_period_start' => now(),
                'current_period_end' => $workspace->trial_ends_at,
                'gateway' => 'trial',
            ]);

            return $workspace->fresh();
        });
    }

    public function transfer(Workspace $workspace, User $actor, int $targetUserId): Workspace
    {
        $actorMembership = $this->membership($workspace, $actor->id);
        if ($actorMembership->role !== Role::OWNER) {
            abort(403, 'Sahiplik devri yalnızca sahip tarafından yapılabilir.');
        }

        $target = $this->membership($workspace, $targetUserId);
        if ($target->user_id === $actor->id) {
            throw ValidationException::withMessages(['user_id' => 'Kendinize devredemezsiniz.']);
        }

        DB::transaction(function () use ($workspace, $actorMembership, $target) {
            $actorMembership->update(['role' => Role::ADMIN, 'ads_write' => true]);
            $target->update(['role' => Role::OWNER, 'ads_write' => true]);
            $workspace->update(['owner_id' => $target->user_id]);
        });

        return $workspace->fresh();
    }

    public function scheduleDeletion(Workspace $workspace, User $actor): Workspace
    {
        if ($this->membership($workspace, $actor->id)->role !== Role::OWNER) {
            abort(403, 'Çalışma alanını yalnızca sahip silebilir.');
        }

        $workspace->update([
            'status' => 'pending_deletion',
            'deletion_scheduled_at' => now()->addDays((int) config('sinsocial.deletion_grace_days')),
        ]);

        Post::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'scheduled')
            ->update(['status' => 'paused']);

        return $workspace->fresh();
    }

    public function cancelDeletion(Workspace $workspace, User $actor): Workspace
    {
        if ($this->membership($workspace, $actor->id)->role !== Role::OWNER) {
            abort(403);
        }

        $subscription = $workspace->subscription()->first();
        $status = $subscription && in_array($subscription->status, ['active', 'trialing'], true) ? 'active' : 'read_only';
        $workspace->update([
            'status' => $status,
            'deletion_scheduled_at' => null,
            'read_only_at' => $status === 'read_only' ? ($workspace->read_only_at ?? now()) : null,
        ]);

        return $workspace->fresh();
    }

    public function membership(Workspace $workspace, int $userId): WorkspaceUser
    {
        $membership = WorkspaceUser::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $userId)
            ->first();

        if (! $membership) {
            abort(404, 'Üye bulunamadı.');
        }

        return $membership;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'calisma-alani';
        $slug = $base;
        $i = 2;
        while (Workspace::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
