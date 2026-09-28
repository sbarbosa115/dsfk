<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

/** Only while the budget is editable. Only the fields in `sent` change; a sent null clears the date. */
final readonly class UpdateMilestone
{
    /**
     * @param list<string> $sent
     */
    public function __construct(
        public int $milestoneId,
        public array $sent,
        public ?string $name = null,
        public ?int $weight = null,
        public ?\DateTimeImmutable $plannedDate = null,
    ) {
    }

    public function has(string $field): bool
    {
        return \in_array($field, $this->sent, true);
    }
}
