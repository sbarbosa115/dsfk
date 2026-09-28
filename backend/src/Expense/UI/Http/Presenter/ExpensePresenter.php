<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Presenter;

use App\Document\Application\Query\AttachmentQueries;
use App\Document\Application\Query\AttachmentView;
use App\Expense\Application\Port\ExpensePlan;
use App\Expense\Application\Port\People;
use App\Expense\Application\Port\SpendingLimits;
use App\Expense\Application\Query\ExpenseQueries;
use App\Expense\Domain\Model\Expense;
use App\Expense\Domain\Model\ExpenseEvent;
use App\Expense\Domain\Model\ExpenseStatus;
use App\Expense\UI\Http\Output\ExpenseEventOutput;
use App\Expense\UI\Http\Output\ExpenseFileOutput;
use App\Expense\UI\Http\Output\ExpenseOutput;
use App\Expense\UI\Http\Output\ExpensePermissionsOutput;
use App\Expense\UI\Http\Output\ExpensePreviousOutput;
use App\Expense\UI\Http\Output\ExpenseRefOutput;
use App\Expense\UI\Http\Output\ExpenseReimbursementOutput;
use App\Expense\UI\Http\Output\ExpenseSummaryOutput;
use App\Finance\Application\Query\FinanceQueries;
use App\Shared\Domain\Money\MinorUnits;

/** Expenses as the signed-in person may act on them, with names resolved and amounts in major units. */
final readonly class ExpensePresenter
{
    public function __construct(
        private ExpensePlan $plan,
        private SpendingLimits $limits,
        private People $people,
        private AttachmentQueries $attachments,
        private FinanceQueries $finance,
        private ExpenseQueries $queries,
    ) {
    }

    /**
     * @param list<Expense> $expenses all of one project
     *
     * @return list<ExpenseOutput>
     */
    public function many(int $projectId, array $expenses, Viewer $viewer, bool $withHistory = false): array
    {
        if ([] === $expenses) {
            return [];
        }
        $currency = $this->limits->currency($projectId);
        $money = static fn (int $minor): string => MinorUnits::toMajor($minor, $currency);
        $stages = $this->plan->stages($projectId);
        $categories = $this->plan->categories($projectId);
        $people = [];
        foreach ($expenses as $e) {
            $people[] = $e->getPaidById();
            if ($withHistory) {
                foreach ($e->getEvents() as $event) {
                    $people[] = $event->getUserId();
                }
            }
        }
        $names = $this->people->names(array_values(array_unique($people)));
        $files = $this->attachments->ofExpenses(array_map(static fn (Expense $e): int => (int) $e->getId(), $expenses));
        $dates = $this->finance->movementDates(array_values(array_filter(array_map(static fn (Expense $e): ?int => $e->getReimbursement()?->getMovementId(), $expenses))));

        return array_map(static function (Expense $e) use ($money, $stages, $categories, $names, $files, $dates, $viewer, $withHistory): ExpenseOutput {
            $r = $e->getReimbursement();

            return new ExpenseOutput(
                (int) $e->getId(),
                $e->getDate()->format('Y-m-d'),
                $money($e->getAmount()),
                $e->getDescription(),
                $e->getSupplier(),
                $e->getInvoiceNumber(),
                new ExpenseRefOutput($e->getStageId(), $stages[$e->getStageId()]['name'] ?? '—'),
                new ExpenseRefOutput($e->getCategoryId(), $categories[$e->getCategoryId()] ?? '—'),
                $e->getPaidFrom()->value,
                new ExpenseRefOutput($e->getPaidById(), $names[$e->getPaidById()] ?? '—'),
                $e->getStatus()->value,
                $e->getRejectionReason(),
                null === $r ? null : new ExpenseReimbursementOutput((int) $r->getId(), $dates[$r->getMovementId()] ?? null, $r->getMethod()->value, $r->getReference()),
                $e->getCreatedAt()->format(\DATE_ATOM),
                array_map(static fn (AttachmentView $a): ExpenseFileOutput => new ExpenseFileOutput($a->id, $a->name, $a->mimeType, $a->size), $files[(int) $e->getId()] ?? []),
                self::permissions($e, $viewer),
                $withHistory ? array_map(static fn (ExpenseEvent $ev): ExpenseEventOutput => new ExpenseEventOutput(
                    $ev->getType(),
                    $names[$ev->getUserId()] ?? '—',
                    $ev->getComment(),
                    self::previous($ev->getPrevious(), $money),
                    $ev->getCreatedAt()->format(\DATE_ATOM),
                ), $e->getEvents()) : null,
            );
        }, $expenses);
    }

    public function one(Expense $expense, Viewer $viewer): ExpenseOutput
    {
        return $this->many($expense->getProjectId(), [$expense], $viewer, true)[0];
    }

    public function summary(int $projectId, Viewer $viewer): ExpenseSummaryOutput
    {
        $currency = $this->limits->currency($projectId);
        $by = $this->queries->outOfPocketByStatus($projectId, $viewer->manager ? null : $viewer->userId);
        $count = static fn (ExpenseStatus ...$s): int => array_sum(array_map(static fn (ExpenseStatus $x): int => $by[$x->value]['count'] ?? 0, $s));
        $sum = static fn (ExpenseStatus ...$s): string => MinorUnits::toMajor(array_sum(array_map(static fn (ExpenseStatus $x): int => $by[$x->value]['total'] ?? 0, $s)), $currency);

        return new ExpenseSummaryOutput(
            $count(ExpenseStatus::Submitted, ExpenseStatus::PmApproved),
            $sum(ExpenseStatus::Submitted, ExpenseStatus::PmApproved),
            $count(ExpenseStatus::Approved),
            $sum(ExpenseStatus::Approved),
            MinorUnits::toMajor($this->limits->teamLeadLimit($projectId), $currency),
        );
    }

    private static function permissions(Expense $e, Viewer $viewer): ExpensePermissionsOutput
    {
        $owner = $e->getPaidById() === $viewer->userId && $e->isCorrectable();
        $decides = $viewer->manager && $e->isWaitingFor($viewer->admin);

        return new ExpensePermissionsOutput(
            edit: $owner,
            attach: $owner || ($viewer->manager && ExpenseStatus::Voided !== $e->getStatus()),
            approve: $decides,
            reject: $decides,
            void: $viewer->admin && ExpenseStatus::Approved === $e->getStatus() && null === $e->getReimbursement(),
            reimburse: $viewer->manager && $e->isReimbursable(),
        );
    }

    /**
     * @param array<string, mixed>|null $previous
     * @param \Closure(int): string     $money
     */
    private static function previous(?array $previous, \Closure $money): ?ExpensePreviousOutput
    {
        if (null === $previous) {
            return null;
        }
        $text = static fn (string $key): string => \is_scalar($previous[$key] ?? null) ? (string) $previous[$key] : '';
        $optional = static fn (string $key): ?string => \is_string($previous[$key] ?? null) ? $previous[$key] : null;

        return new ExpensePreviousOutput(
            $text('stage'),
            $text('category'),
            $text('date'),
            $money(\is_int($previous['amount'] ?? null) ? $previous['amount'] : (int) $text('amount')),
            $text('description'),
            $optional('supplier'),
            $optional('invoiceNumber'),
        );
    }
}
