<?php

declare(strict_types=1);

namespace App\Finance\Application\Port;

final readonly class PlanStage
{
    public function __construct(
        public int $id,
        public string $name,
        /** PENDING, IN_PROGRESS or COMPLETED */
        public string $status,
        /** Minor units. */
        public int $budget,
    ) {
    }

    public function isCompleted(): bool
    {
        return 'COMPLETED' === $this->status;
    }
}
