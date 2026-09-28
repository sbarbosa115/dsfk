<?php

declare(strict_types=1);

namespace App\Expense\Domain\Event;

/** A Team Lead's expense waits for the PM (new, or corrected). */
final readonly class ExpenseSubmitted
{
    public function __construct(public int $expenseId, public int $projectId)
    {
    }
}
