<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

final readonly class AddStage
{
    public function __construct(
        public int $projectId,
        public string $name,
        public ?\DateTimeImmutable $plannedStart = null,
        public ?\DateTimeImmutable $plannedEnd = null,
    ) {
    }
}
