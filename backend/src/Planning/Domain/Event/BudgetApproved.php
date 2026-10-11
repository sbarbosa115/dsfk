<?php

declare(strict_types=1);

namespace App\Planning\Domain\Event;

/** The Admin approved the budget: it is locked and the project is active. */
final readonly class BudgetApproved
{
    public function __construct(
        public int $projectId,
        public int $byUserId,
        /** The amendment's number; null for the budget itself. */
        public ?int $amendment = null,
    )
    {
    }
}
