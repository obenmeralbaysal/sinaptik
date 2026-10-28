<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Services\TrialService;
use App\Support\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ApplyWorkspaceContext
{
    public function __construct(private TrialService $trials) {}

    public function handle(Request $request, Closure $next): Response
    {
        $started = DB::transactionLevel() === 0;
        if ($started) {
            DB::beginTransaction();
        }

        try {
            $this->bind($request);
            $response = $next($request);
            if ($started) {
                DB::commit();
            }

            return $response;
        } catch (\Throwable $exception) {
            if ($started && DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $exception;
        }
    }

    private function bind(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $workspaceId = (int) ($request->header('X-Workspace-Id') ?: $user->current_workspace_id);
        if (! $workspaceId) {
            abort(409, 'Çalışma alanı seçilmedi.');
        }

        $membership = WorkspaceUser::query()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->first();
        if (! $membership) {
            abort(403, 'Bu çalışma alanına erişiminiz yok.');
        }

        $workspace = Workspace::query()->findOrFail($workspaceId);
        app(WorkspaceContext::class)->activate($workspace->id);
        $workspace = $this->trials->sync($workspace);
        $request->attributes->set('workspace', $workspace);
        $request->attributes->set('membership', $membership);
    }
}
