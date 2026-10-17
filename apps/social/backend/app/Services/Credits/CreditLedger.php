<?php

namespace App\Services\Credits;

use App\Models\CreditLedgerEntry;
use App\Models\Workspace;
use RuntimeException;

class CreditLedger
{
    public function balance(Workspace $workspace, string $type): int
    {
        $latest = CreditLedgerEntry::query()
            ->where('workspace_id', $workspace->id)
            ->where('currency_type', $type)
            ->latest('id')
            ->first();

        return (int) ($latest->balance_after ?? 0);
    }

    public function post(Workspace $workspace, string $type, int $delta, string $reason, ?string $reference = null, array $meta = []): CreditLedgerEntry
    {
        $next = $this->balance($workspace, $type) + $delta;
        if ($next < 0) {
            throw new RuntimeException('Yetersiz kredi.');
        }

        return CreditLedgerEntry::query()->create([
            'workspace_id' => $workspace->id,
            'currency_type' => $type,
            'delta' => $delta,
            'balance_after' => $next,
            'reason' => $reason,
            'reference' => $reference,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
