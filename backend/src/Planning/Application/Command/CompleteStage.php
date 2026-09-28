<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

/** The Admin closes a stage whose milestones are all met; its leftover money moves on. */
final readonly class CompleteStage
{
    public function __construct(public int $stageId, public \DateTimeImmutable $actualEnd, public int $actorId)
    {
    }
}
