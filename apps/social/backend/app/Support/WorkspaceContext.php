<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class WorkspaceContext
{
    private ?int $workspaceId = null;

    private bool $bypass = false;

    public function id(): ?int
    {
        return $this->bypass ? null : $this->workspaceId;
    }

    public function bypass(): bool
    {
        return $this->bypass;
    }

    public function activate(?int $workspaceId, bool $bypass = false): void
    {
        $this->workspaceId = $workspaceId;
        $this->bypass = $bypass;

        $local = DB::transactionLevel() > 0 ? 'true' : 'false';
        $pdo = DB::connection()->getPdo();
        $bypassSql = $pdo->quote($bypass ? 'on' : 'off');
        $idSql = $pdo->quote($workspaceId ? (string) $workspaceId : '');

        DB::unprepared("select set_config('app.bypass_rls', {$bypassSql}, {$local})");
        DB::unprepared("select set_config('app.workspace_id', {$idSql}, {$local})");
    }

    public function allowMedia(int $mediaId): void
    {
        $local = DB::transactionLevel() > 0 ? 'true' : 'false';
        $idSql = DB::connection()->getPdo()->quote((string) $mediaId);
        DB::unprepared("select set_config('app.media_access_id', {$idSql}, {$local})");
    }
}
