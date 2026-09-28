<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Domain\Model\ProjectStatus;

final readonly class CreateProject
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        /** null: the default currency in Settings */
        public ?string $currency = null,
        public ?ProjectStatus $status = null,
        public ?\DateTimeImmutable $plannedStart = null,
        public ?\DateTimeImmutable $plannedEnd = null,
    ) {
    }
}
