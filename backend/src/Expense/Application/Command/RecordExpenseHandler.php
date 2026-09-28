<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

use App\Expense\Application\Port\ExpenseFunds;
use App\Expense\Application\Port\ExpensePlan;
use App\Expense\Application\Port\SpendingLimits;
use App\Expense\Domain\Event\ExpenseSubmitted;
use App\Expense\Domain\Event\PettyCashUsed;
use App\Expense\Domain\Event\SpendingRecorded;
use App\Expense\Domain\Model\Expense;
use App\Expense\Domain\Model\PaidFrom;
use App\Expense\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventPublisher;
use App\Shared\Application\Bus\NewId;
use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\InvalidValue;
use Psr\Clock\ClockInterface;

final readonly class RecordExpenseHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenses,
        private ExpensePlan $plan,
        private SpendingLimits $limits,
        private ExpenseFunds $funds,
        private EventPublisher $events,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RecordExpense $c): NewId
    {
        if (!$this->plan->isApproved($c->projectId)) {
            throw new Conflict('budget_not_approved');
        }
        $paidFrom = $c->paidFrom ?? ($c->manager ? null : PaidFrom::OutOfPocket);
        if (null === $paidFrom) {
            throw InvalidValue::field('paidFrom', 'Elige si el gasto se paga desde la etapa o desde la caja menor.');
        }
        if ($c->manager === (PaidFrom::OutOfPocket === $paidFrom)) {
            throw InvalidValue::field('paidFrom', $c->manager ? 'Elige si el gasto se paga desde la etapa o desde la caja menor.' : 'Los líderes de equipo registran gastos pagados con su dinero.');
        }
        $d = CheckedDetails::of($c->details, $c->projectId, $this->plan, $this->limits);
        $in = $c->details;
        $expense = Expense::record($c->projectId, $in->stageId, $in->categoryId, $in->date, $d->amount, $in->description, $in->supplier, $in->invoiceNumber, $paidFrom, $c->actorId, $this->clock->now());
        $this->expenses->add($expense);

        if (PaidFrom::OutOfPocket === $paidFrom) {
            $this->events->publish(new ExpenseSubmitted(...self::ids($expense)));

            return NewId::of($expense);
        }
        $expense->linkMovement($this->funds->pay($c->projectId, $paidFrom->value, $in->stageId, $in->categoryId, $d->amount, $in->date, $expense->getDescription(), $c->actorId));
        $this->events->publish(new SpendingRecorded(...self::ids($expense)));
        if (PaidFrom::PettyCash === $paidFrom) {
            $this->events->publish(new PettyCashUsed($c->projectId));
        }

        return NewId::of($expense);
    }

    /**
     * @return array{expenseId: int, projectId: int}
     */
    public static function ids(Expense $expense): array
    {
        return ['expenseId' => (int) $expense->getId(), 'projectId' => $expense->getProjectId()];
    }
}
