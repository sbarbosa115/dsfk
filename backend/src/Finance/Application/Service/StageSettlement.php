<?php

declare(strict_types=1);

namespace App\Finance\Application\Service;

use App\Finance\Domain\Model\FundMovement;
use App\Finance\Domain\Repository\LedgerRepository;
use Psr\Clock\ClockInterface;

/**
 * What completing a stage does to its money, in the Planning command's transaction: whatever is left moves to the
 * open stage the Admin chose, or to the project fund.
 */
final readonly class StageSettlement
{
    public function __construct(private LedgerRepository $ledger, private ClockInterface $clock)
    {
    }

    public function settle(int $projectId, int $stageId, ?int $toStageId, \DateTimeImmutable $date, int $actorId): void
    {
        $this->ledger->lock($projectId);
        $left = $this->ledger->balances($projectId)->stage($stageId);
        if ($left <= 0) {
            return;
        }
        $this->ledger->add(FundMovement::carryOver($projectId, $stageId, $toStageId, $left, $date, $actorId, $this->clock->now()));
    }
}
