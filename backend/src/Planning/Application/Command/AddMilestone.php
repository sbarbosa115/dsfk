<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

final readonly class AddMilestone
{
    public function __construct(
        public int $stageId,
        public string $name,
        /** Basis points of the stage (2500 = 25 %). */
        public int $weight,
        public ?\DateTimeImmutable $plannedDate = null,
    ) {
    }
}
