<?php

declare(strict_types=1);

namespace App\Planning\Domain\Event;

/** A PM (or Admin) submitted the budget for approval. */
final readonly class BudgetSubmitted
{
    public function __construct(public int $projectId, public int $byUserId)
    {
    }
}
