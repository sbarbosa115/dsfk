<?php

declare(strict_types=1);

namespace App\Notification\Application\Handler;

use App\Expense\Domain\Event\ExpensesReimbursed;
use App\Notification\Application\Port\NotificationFacts;
use App\Notification\Application\Port\Outbox;
use App\Notification\Application\Port\Recipients;
use App\Shared\Application\Bus\EventHandler;
use App\Shared\Domain\Money\MinorUnits;

/** Each Team Lead hears which of their expenses were paid back, and how much in all. */
final readonly class NotifyReimbursed implements EventHandler
{
    public function __construct(private Recipients $recipients, private NotificationFacts $facts, private Outbox $outbox)
    {
    }

    public function __invoke(ExpensesReimbursed $event): void
    {
        $project = $this->facts->project($event->projectId);
        $byPerson = [];
        foreach ($event->expenseIds as $id) {
            $expense = $this->facts->expense($id);
            if (null !== $expense) {
                $byPerson[$expense['paidById']][] = $expense;
            }
        }
        foreach ($byPerson as $personId => $expenses) {
            $total = array_sum(array_column($expenses, 'amount'));
            $this->outbox->send([$this->recipients->person($personId)], 'Te reembolsaron gastos de '.$project['name'], 'reimbursed', [
                'project' => $project,
                'expenses' => $expenses,
                'total' => MinorUnits::format($total, $project['currency']),
            ]);
        }
    }
}
