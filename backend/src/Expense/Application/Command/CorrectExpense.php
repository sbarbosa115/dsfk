<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

/** The Team Lead corrects their pending or rejected expense; it goes back to the PM. */
final readonly class CorrectExpense
{
    public function __construct(public int $expenseId, public int $actorId, public ExpenseDetails $details)
    {
    }
}
