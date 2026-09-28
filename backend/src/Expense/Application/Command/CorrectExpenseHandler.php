<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

use App\Expense\Application\Port\ExpensePlan;
use App\Expense\Application\Port\SpendingLimits;
use App\Expense\Domain\Event\ExpenseSubmitted;
use App\Expense\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventPublisher;
use Psr\Clock\ClockInterface;

final readonly class CorrectExpenseHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenses,
        private ExpensePlan $plan,
        private SpendingLimits $limits,
        private EventPublisher $events,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CorrectExpense $c): void
    {
        $expense = $this->expenses->forUpdate($c->expenseId);
        $d = CheckedDetails::of($c->details, $expense->getProjectId(), $this->plan, $this->limits);
        $in = $c->details;
        $names = [
            'stage' => $d->stages[$expense->getStageId()]['name'] ?? '—',
            'category' => $d->categories[$expense->getCategoryId()] ?? '—',
        ];
        $expense->correct($c->actorId, $in->stageId, $in->categoryId, $in->date, $d->amount, $in->description, $in->supplier, $in->invoiceNumber, $names, $this->clock->now());
        $this->events->publish(new ExpenseSubmitted((int) $expense->getId(), $expense->getProjectId()));
    }
}
