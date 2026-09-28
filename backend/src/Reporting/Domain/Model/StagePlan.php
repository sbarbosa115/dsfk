<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Model;

/** A stage as the dashboards read it: its budget, dates, progress and milestones. */
final readonly class StagePlan
{
    /**
     * @param list<array{weight: int, plannedDate: ?\DateTimeImmutable, completed: bool}> $milestones
     */
    public function __construct(
        public int $id,
        public string $name,
        /** PENDING, IN_PROGRESS or COMPLETED */
        public string $status,
        /** Minor units. */
        public int $budget,
        /** Basis points met. */
        public int $progress,
        public ?\DateTimeImmutable $plannedStart,
        public ?\DateTimeImmutable $plannedEnd,
        public ?\DateTimeImmutable $actualStart,
        public ?\DateTimeImmutable $actualEnd,
        public array $milestones,
    ) {
    }

    public function isCompleted(): bool
    {
        return 'COMPLETED' === $this->status;
    }
}
