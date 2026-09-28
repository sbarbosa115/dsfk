<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

use App\Expense\Application\Port\ExpenseFunds;
use App\Expense\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Bus\CommandHandler;
use Psr\Clock\ClockInterface;

final readonly class VoidExpenseHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $expenses, private ExpenseFunds $funds, private ClockInterface $clock)
    {
    }

    public function __invoke(VoidExpense $c): void
    {
        $expense = $this->expenses->get($c->expenseId);
        $expense->void($c->actorId, $c->reason, $this->clock->now());
        if (null !== $expense->getMovementId()) {
            $this->funds->refund($expense->getMovementId(), $c->actorId, $c->reason);
        }
    }
}
