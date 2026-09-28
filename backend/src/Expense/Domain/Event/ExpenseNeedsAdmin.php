<?php

declare(strict_types=1);

namespace App\Expense\Domain\Event;

/** The PM approved an expense above the Team Lead limit, so an Admin decides. */
final readonly class ExpenseNeedsAdmin
{
    public function __construct(public int $expenseId, public int $projectId)
    {
    }
}
