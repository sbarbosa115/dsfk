<?php

declare(strict_types=1);

namespace App\Expense\Domain\Event;

/** An expense now counts against the budget (for the budget warnings). */
final readonly class SpendingRecorded
{
    public function __construct(public int $expenseId, public int $projectId)
    {
    }
}
