<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

/** An Admin voids an approved expense recorded by mistake; its money goes back where it came from. */
final readonly class VoidExpense
{
    public function __construct(public int $expenseId, public int $actorId, public string $reason)
    {
    }
}
