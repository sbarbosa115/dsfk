<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

use App\Expense\Application\Port\ExpenseFunds;
use App\Expense\Application\Port\People;
use App\Expense\Domain\Event\ExpensesReimbursed;
use App\Expense\Domain\Event\PettyCashUsed;
use App\Expense\Domain\Model\Expense;
use App\Expense\Domain\Model\Reimbursement;
use App\Expense\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventPublisher;
use App\Shared\Domain\Error\InvalidValue;
use Psr\Clock\ClockInterface;

final readonly class ReimburseExpensesHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenses,
        private ExpenseFunds $funds,
        private People $people,
        private EventPublisher $events,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(ReimburseExpenses $c): void
    {
        $ids = array_values(array_unique($c->expenseIds));
        $expenses = $this->expenses->manyForUpdate($ids);
        if (\count($expenses) !== \count($ids)) {
            throw InvalidValue::field('expenseIds', 'Expense not found.');
        }
        foreach ($expenses as $expense) {
            if ($expense->getProjectId() !== $c->projectId || !$expense->isReimbursable()) {
                throw InvalidValue::field('expenseIds', 'Only approved Team Lead expenses can be paid back.');
            }
        }
        $total = array_sum(array_map(static fn (Expense $e): int => $e->getAmount(), $expenses));
        $names = $this->people->names(array_values(array_unique(array_map(static fn (Expense $e): int => $e->getPaidById(), $expenses))));
        $note = 'Reembolso: '.implode(', ', array_map(static fn (Expense $e): string => ($names[$e->getPaidById()] ?? '—').' – '.$e->getDescription(), $expenses));

        $movementId = $this->funds->payBack($c->projectId, $total, $c->date, mb_substr($note, 0, 2000), $c->actorId);
        $reimbursement = new Reimbursement($c->projectId, $movementId, $c->method, $c->reference);
        $this->expenses->addReimbursement($reimbursement);
        foreach ($expenses as $expense) {
            $expense->markReimbursed($reimbursement, $c->actorId, $this->clock->now());
        }
        $this->events->publish(new ExpensesReimbursed($c->projectId, $ids), new PettyCashUsed($c->projectId, $total));
    }
}
