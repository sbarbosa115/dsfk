<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

/** The PM (up to the Team Lead limit) or an Admin approves a Team Lead's expense that has a receipt. */
final readonly class ApproveExpense
{
    public function __construct(public int $expenseId, public int $actorId, public bool $admin)
    {
    }
}
