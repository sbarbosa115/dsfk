<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

use App\Expense\Application\Port\Receipts;
use App\Expense\Application\Port\SpendingLimits;
use App\Expense\Domain\Event\ExpenseNeedsAdmin;
use App\Expense\Domain\Event\SpendingRecorded;
use App\Expense\Domain\Model\ExpenseStatus;
use App\Expense\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventPublisher;
use Psr\Clock\ClockInterface;

final readonly class ApproveExpenseHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenses,
        private SpendingLimits $limits,
        private Receipts $receipts,
        private EventPublisher $events,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(ApproveExpense $c): void
    {
        $expense = $this->expenses->get($c->expenseId);
        $expense->approve($c->actorId, $c->admin, $this->limits->teamLeadLimit($expense->getProjectId()), $this->receipts->has($c->expenseId), $this->clock->now());
        $this->events->publish(ExpenseStatus::PmApproved === $expense->getStatus()
            ? new ExpenseNeedsAdmin($c->expenseId, $expense->getProjectId())
            : new SpendingRecorded($c->expenseId, $expense->getProjectId()));
    }
}
