<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogger
{
    public function log(
        ?int $workspaceId,
        string $actorType,
        ?int $actorId,
        string $action,
        string $result = 'ok',
        ?string $tool = null,
        ?string $paramsSummary = null,
        ?int $costMinor = null,
        ?int $budgetDelta = null,
        ?string $ip = null,
        array $meta = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'workspace_id' => $workspaceId,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'tool' => $tool,
            'params_summary' => $paramsSummary,
            'result' => $result,
            'cost_minor' => $costMinor,
            'budget_delta_minor' => $budgetDelta,
            'ip' => $ip,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
