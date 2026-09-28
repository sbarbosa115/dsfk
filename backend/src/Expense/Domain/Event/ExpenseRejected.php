<?php

declare(strict_types=1);

namespace App\Expense\Domain\Event;

/** The PM or an Admin sent an expense back to its Team Lead. */
final readonly class ExpenseRejected
{
    public function __construct(public int $expenseId, public int $projectId)
    {
    }
}
