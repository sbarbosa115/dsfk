<?php

declare(strict_types=1);

namespace App\Planning\Infrastructure\Finance;

use App\Finance\Application\Service\StageSettlement;
use App\Planning\Application\Port\StageFunds;

final readonly class FinanceStageFunds implements StageFunds
{
    public function __construct(private StageSettlement $settlement)
    {
    }

    public function settle(int $projectId, int $stageId, ?int $nextStageId, \DateTimeImmutable $date, int $actorId): void
    {
        $this->settlement->settle($projectId, $stageId, $nextStageId, $date, $actorId);
    }
}
