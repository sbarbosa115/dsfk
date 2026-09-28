<?php

declare(strict_types=1);

namespace App\Planning\Domain\Event;

/** The Admin sent the budget back to the PM, with a comment. */
final readonly class BudgetReturned
{
    public function __construct(public int $projectId, public int $byUserId, public string $comment)
    {
    }
}
