<?php

namespace Tests\Support;

use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Services\WorkspaceService;
use Database\Seeders\DatabaseSeeder;

class World
{
    public function __construct(
        public User $owner,
        public Workspace $workspace,
        public WorkspaceUser $membership,
    ) {}

    public static function boot(): self
    {
        (new DatabaseSeeder)->run();
        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'password' => 'password',
        ]);
        $workspace = app(WorkspaceService::class)->create($owner, 'Sinapp');
        $membership = WorkspaceUser::query()->where('workspace_id', $workspace->id)->where('user_id', $owner->id)->firstOrFail();

        return new self($owner, $workspace->fresh(), $membership);
    }

    public function switchPlan(string $code): void
    {
        $plan = Plan::query()->where('code', $code)->firstOrFail();
        $this->workspace->subscription()->update(['plan_id' => $plan->id, 'status' => 'active']);
    }
}
