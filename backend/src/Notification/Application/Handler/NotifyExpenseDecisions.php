<?php

declare(strict_types=1);

namespace App\Notification\Application\Handler;

use App\Expense\Domain\Event\ExpenseNeedsAdmin;
use App\Expense\Domain\Event\ExpenseRejected;
use App\Expense\Domain\Event\ExpenseSubmitted;
use App\Notification\Application\Port\NotificationFacts;
use App\Notification\Application\Port\Outbox;
use App\Notification\Application\Port\Recipients;
use App\Shared\Application\Bus\EventHandler;
use App\Shared\Domain\Money\MinorUnits;

/**
 * A Team Lead's expense on its way: the PM approves it, an Admin too above the limit, and its Team Lead hears
 * when it is rejected.
 */
final readonly class NotifyExpenseDecisions implements EventHandler
{
    public function __construct(private Recipients $recipients, private NotificationFacts $facts, private Outbox $outbox)
    {
    }

    public function __invoke(ExpenseSubmitted|ExpenseNeedsAdmin|ExpenseRejected $event): void
    {
        $expense = $this->facts->expense($event->expenseId);
        if (null === $expense) {
            return;
        }
        $project = $this->facts->project($event->projectId);
        $context = ['project' => $project, 'expense' => $expense, 'amount' => MinorUnits::format($expense['amount'], $project['currency'])];
        $subject = ['expense' => $expense['description']];

        match (true) {
            $event instanceof ExpenseSubmitted => $this->outbox->send([$this->recipients->projectManager($event->projectId)], 'expense_submitted', $subject, $context),
            $event instanceof ExpenseNeedsAdmin => $this->outbox->send($this->recipients->admins(), 'expense_needs_admin', $subject, $context),
            default => $this->outbox->send([$this->recipients->person($expense['paidById'])], 'expense_rejected', $subject, $context),
        };
    }
}
