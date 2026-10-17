<?php

namespace App\Services\Credits;

use App\Models\Workspace;
use RuntimeException;

class XCostMeter
{
    public function __construct(private CreditLedger $ledger) {}

    public function costCredits(string $action): int
    {
        $milli = (int) (config('x.prices_milli_cents')[$action] ?? 0);
        $unit = (int) config('x.credit_milli_cents');

        return (int) max(1, (int) ceil($milli / max(1, $unit)));
    }

    public function debit(Workspace $workspace, string $action, string $reference): int
    {
        $cost = $this->costCredits($action);
        if ($this->ledger->balance($workspace, 'x_credit') < $cost) {
            throw new RuntimeException('X kredisi yetersiz.', 402);
        }
        $this->ledger->post($workspace, 'x_credit', -$cost, 'x_'.$action, $reference, ['action' => $action]);

        return $cost;
    }
}
