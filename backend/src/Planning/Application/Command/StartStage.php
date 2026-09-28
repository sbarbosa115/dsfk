<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

/** After approval: the stage's work began on that date (not in the future). */
final readonly class StartStage
{
    public function __construct(public int $stageId, public \DateTimeImmutable $actualStart)
    {
    }
}
