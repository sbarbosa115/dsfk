<?php

declare(strict_types=1);

namespace App\Planning\Application\Port;

/** The Finance context, which settles a completed stage's money in the same transaction. */
interface StageFunds
{
    /** Moves what is left of the stage to $toStageId, or to the project fund when it is null. */
    public function settle(int $projectId, int $stageId, ?int $toStageId, \DateTimeImmutable $date, int $actorId): void;
}
