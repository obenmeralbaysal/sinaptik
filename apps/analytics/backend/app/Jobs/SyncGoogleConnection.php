<?php

namespace App\Jobs;

use App\Models\Workspace;
use App\Services\Google\GoogleSyncRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncGoogleConnection implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $workspaceId) {}

    public function handle(GoogleSyncRunner $runner): void
    {
        $workspace = Workspace::query()->findOrFail($this->workspaceId);
        $runner->run($workspace);
    }
}
