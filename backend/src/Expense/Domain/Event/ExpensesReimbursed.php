<?php

declare(strict_types=1);

namespace App\Expense\Domain\Event;

/** Team Lead expenses were paid back from petty cash. */
final readonly class ExpensesReimbursed
{
    /**
     * @param list<int> $expenseIds
     */
    public function __construct(public int $projectId, public array $expenseIds)
    {
    }
}
