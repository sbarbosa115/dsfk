<?php

namespace App\Service;

use App\Enum\LedgerAccount;
use App\Enum\MovementType;

/**
 * Money per account of a project, in minor units, computed from non-voided ledger entries.
 * Inflows and outflows are kept per movement type so reports can explain each balance.
 */
final class Balances
{
    /**
     * @param array<string, array<string, array{in: int, out: int}>> $flows account key => movement type => flows
     */
    public function __construct(private readonly array $flows)
    {
    }

    public static function key(LedgerAccount $account, ?int $stageId = null): string
    {
        return LedgerAccount::Stage === $account ? 'STAGE:'.$stageId : $account->value;
    }

    /** Money received through a type of movement (positive). */
    public function in(string $key, MovementType $type): int
    {
        return $this->flows[$key][$type->value]['in'] ?? 0;
    }

    /** Money that left through a type of movement (positive number). */
    public function out(string $key, MovementType $type): int
    {
        return -($this->flows[$key][$type->value]['out'] ?? 0);
    }

    public function balance(string $key): int
    {
        $total = 0;
        foreach ($this->flows[$key] ?? [] as $flow) {
            $total += $flow['in'] + $flow['out'];
        }

        return $total;
    }

    public function stage(int $stageId): int
    {
        return $this->balance(self::key(LedgerAccount::Stage, $stageId));
    }
}
