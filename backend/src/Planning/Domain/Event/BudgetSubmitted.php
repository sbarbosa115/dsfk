<?php

declare(strict_types=1);

namespace App\Planning\Domain\Event;

/** A PM (or Admin) submitted the budget, or an amendment to it, for approval. */
final readonly class BudgetSubmitted
{
    public function __construct(
        public int $projectId,
        public int $byUserId,
        /** The amendment's number; null for the budget itself. */
        public ?int $amendment = null,
    ) {
    }
}
