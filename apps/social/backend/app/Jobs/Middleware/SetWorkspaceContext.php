<?php

namespace App\Jobs\Middleware;

use App\Support\WorkspaceContext;
use Illuminate\Support\Facades\DB;

class SetWorkspaceContext
{
    public function __construct(private int $workspaceId, private bool $bypass = false) {}

    public function handle(object $job, \Closure $next): void
    {
        $started = DB::transactionLevel() === 0;
        if ($started) {
            DB::beginTransaction();
        }

        try {
            app(WorkspaceContext::class)->activate($this->bypass ? null : $this->workspaceId, $this->bypass);
            $next($job);
            if ($started) {
                DB::commit();
            }
        } catch (\Throwable $exception) {
            if ($started && DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            if (method_exists($job, 'onWorkspaceFailure')) {
                DB::transaction(function () use ($job, $exception) {
                    app(WorkspaceContext::class)->activate($this->bypass ? null : $this->workspaceId, $this->bypass);
                    $job->onWorkspaceFailure($exception);
                });
            }
            throw $exception;
        }
    }
}
