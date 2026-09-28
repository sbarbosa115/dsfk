<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

use App\Expense\Domain\Event\ExpenseRejected;
use App\Expense\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventPublisher;
use Psr\Clock\ClockInterface;

final readonly class RejectExpenseHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $expenses, private EventPublisher $events, private ClockInterface $clock)
    {
    }

    public function __invoke(RejectExpense $c): void
    {
        $expense = $this->expenses->get($c->expenseId);
        $expense->reject($c->actorId, $c->admin, $c->reason, $this->clock->now());
        $this->events->publish(new ExpenseRejected($c->expenseId, $expense->getProjectId()));
    }
}
