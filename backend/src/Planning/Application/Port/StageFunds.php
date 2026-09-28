<?php

declare(strict_types=1);

namespace App\Planning\Application\Port;

/** The Finance context, which settles a completed stage's money in the same transaction. */
interface StageFunds
{
    /** Moves what is left of the stage to the next open stage, or to the contingency when $nextStageId is null. */
    public function settle(int $projectId, int $stageId, ?int $nextStageId, \DateTimeImmutable $date, int $actorId): void;
}
