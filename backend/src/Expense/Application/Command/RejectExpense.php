<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

/** The PM or an Admin sends a Team Lead's expense back, saying why. */
final readonly class RejectExpense
{
    public function __construct(public int $expenseId, public int $actorId, public bool $admin, public string $reason)
    {
    }
}
