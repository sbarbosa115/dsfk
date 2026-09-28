<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Domain\Model\ProjectStatus;

/** An Admin edits a project. Only the fields listed in `sent` change; a sent null clears a date. */
final readonly class UpdateProject
{
    /**
     * @param list<string> $sent names of the fields the request carried
     */
    public function __construct(
        public int $projectId,
        public array $sent,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $currency = null,
        public ?ProjectStatus $status = null,
        public ?\DateTimeImmutable $plannedStart = null,
        public ?\DateTimeImmutable $plannedEnd = null,
    ) {
    }

    public function has(string $field): bool
    {
        return \in_array($field, $this->sent, true);
    }
}
