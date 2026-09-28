<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

/**
 * The name can always be fixed; the planned dates are part of the baseline and change only while the budget is
 * editable. Only the fields in `sent` change; a sent null clears a date.
 */
final readonly class UpdateStage
{
    /**
     * @param list<string> $sent
     */
    public function __construct(
        public int $stageId,
        public array $sent,
        public ?string $name = null,
        public ?\DateTimeImmutable $plannedStart = null,
        public ?\DateTimeImmutable $plannedEnd = null,
    ) {
    }

    public function has(string $field): bool
    {
        return \in_array($field, $this->sent, true);
    }
}
