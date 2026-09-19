<?php

namespace App\Service;

use App\Entity\Expense;
use App\Entity\FundMovement;
use App\Entity\Project;
use App\Entity\Reimbursement;
use App\Entity\User;
use App\Enum\ExpenseStatus;
use App\Enum\LedgerAccount;
use App\Enum\MovementType;
use App\Enum\PaidFrom;
use App\Enum\PaymentMethod;
use App\Enum\ProjectRole;
use App\Exception\ApiProblem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Expense rules:
 * - PM/Admin expenses are paid from a stage balance or the caja menor and count immediately.
 * - Team Lead expenses are paid out of pocket: PM approves (Admin too above the limit),
 *   then the PM reimburses them from the caja menor.
 * - Money never goes below zero: the account must hold enough when it is paid.
 */
class ExpenseService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LedgerService $ledger,
        private readonly PettyCashService $pettyCash,
        private readonly ProjectAccess $access,
        private readonly SettingsService $settings,
        private readonly Alerts $alerts,
    ) {
    }

    /** Called after the details are set on a new expense. */
    public function create(Expense $expense, User $by): void
    {
        $project = $expense->getProject();
        $role = $this->access->role($project, $by);
        $isTeamLead = ProjectRole::TeamLead->value === $role;

        if ($isTeamLead !== (PaidFrom::OutOfPocket === $expense->getPaidFrom())) {
            throw ApiProblem::field('paidFrom', $isTeamLead
                ? 'Los líderes de equipo registran gastos pagados con su dinero.'
                : 'Elige si el gasto se paga desde la etapa o desde la caja menor.');
        }
        if (PaidFrom::Stage === $expense->getPaidFrom() && $expense->getStage()->isCompleted()) {
            throw ApiProblem::field('stageId', 'La etapa ya está finalizada y no tiene fondos.');
        }

        $this->em->persist($expense);
        $expense->record('CREATED', $by);
        if ($isTeamLead) {
            $this->alerts->expenseSubmitted($expense);
        } else {
            $this->takeMoney($expense, $by);
            $this->alerts->spendingIncreased($expense);
        }
    }

    public function update(Expense $expense, User $by, array $previous): void
    {
        if ($expense->getPaidBy()->getId() !== $by->getId()) {
            throw new AccessDeniedHttpException();
        }
        $expense->resubmit();
        $expense->record('EDITED', $by, null, $previous);
        $this->alerts->expenseSubmitted($expense);
    }

    public function approve(Expense $expense, User $by): void
    {
        $project = $expense->getProject();
        $role = $this->access->role($project, $by);
        if (!$this->access->isManager($project, $by)) {
            throw new AccessDeniedHttpException();
        }
        if ($expense->getAttachments()->isEmpty()) {
            throw new \DomainException('receipt_required');
        }

        if (ProjectAccess::ADMIN === $role) {
            $expense->approve();
            $expense->record('APPROVED', $by);
            $this->alerts->spendingIncreased($expense);

            return;
        }

        // Project manager.
        if (ExpenseStatus::PmApproved === $expense->getStatus()) {
            throw new \DomainException('expense_awaiting_admin');
        }
        if ($expense->getAmount() > $this->teamLeadLimit($project)) {
            $expense->markPmApproved();
            $expense->record('PM_APPROVED', $by);
            $this->alerts->expenseNeedsAdmin($expense);
        } else {
            $expense->approve();
            $expense->record('APPROVED', $by);
            $this->alerts->spendingIncreased($expense);
        }
    }

    public function reject(Expense $expense, User $by, string $reason): void
    {
        $project = $expense->getProject();
        if (!$this->access->isManager($project, $by)) {
            throw new AccessDeniedHttpException();
        }
        if (ExpenseStatus::PmApproved === $expense->getStatus() && ProjectAccess::ADMIN !== $this->access->role($project, $by)) {
            throw new \DomainException('expense_awaiting_admin');
        }
        $expense->reject($reason);
        $expense->record('REJECTED', $by, trim($reason));
        $this->alerts->expenseRejected($expense);
    }

    public function void(Expense $expense, User $by, string $reason): void
    {
        if ($expense->getReimbursement()) {
            throw new \DomainException('expense_invalid_status');
        }
        $movement = $expense->getMovement();
        if (null !== $movement) {
            $this->pettyCash->assertMutable($movement);
            $this->ledger->void($movement, $by, $reason);
        }
        $expense->void();
        $expense->record('VOIDED', $by, trim($reason));
    }

    /**
     * @param list<Expense> $expenses
     */
    public function reimburse(Project $project, array $expenses, \DateTimeImmutable $date, PaymentMethod $method, ?string $reference, User $by): Reimbursement
    {
        if (!$this->access->isManager($project, $by)) {
            throw new AccessDeniedHttpException();
        }

        $total = 0;
        foreach ($expenses as $expense) {
            if ($expense->getProject() !== $project || PaidFrom::OutOfPocket !== $expense->getPaidFrom() || ExpenseStatus::Approved !== $expense->getStatus()) {
                throw ApiProblem::field('expenseIds', 'Solo se pueden reembolsar gastos aprobados de líderes de equipo.');
            }
            $total += $expense->getAmount();
        }
        $this->assertFunds($project, LedgerAccount::PettyCash->value, $total, 'expenseIds');
        $this->alerts->pettyCashOutflow($project, $total);

        $movement = new FundMovement($project, MovementType::Reimbursement, $date, $by, 'Reembolso a líderes de equipo');
        $movement->addEntry(LedgerAccount::PettyCash, -$total);
        $this->pettyCash->assign($movement);
        $this->em->persist($movement);

        $reimbursement = new Reimbursement($project, $movement, $method, $reference);
        $this->em->persist($reimbursement);
        foreach ($expenses as $expense) {
            $expense->markReimbursed($reimbursement);
            $reimbursement->getExpenses()->add($expense);
            $expense->record('REIMBURSED', $by);
        }
        $this->alerts->reimbursed($expenses);

        return $reimbursement;
    }

    public function teamLeadLimit(Project $project): int
    {
        return MoneyConverter::toMinor((string) $this->settings->get(SettingsService::TEAM_LEAD_EXPENSE_LIMIT), $project->getCurrency());
    }

    private function takeMoney(Expense $expense, User $by): void
    {
        $project = $expense->getProject();
        $movement = new FundMovement($project, MovementType::Expense, $expense->getDate(), $by, $expense->getDescription());

        if (PaidFrom::Stage === $expense->getPaidFrom()) {
            $this->assertFunds($project, Balances::key(LedgerAccount::Stage, $expense->getStage()->getId()), $expense->getAmount(), 'amount');
            $movement->addEntry(LedgerAccount::Stage, -$expense->getAmount(), $expense->getStage(), $expense->getCategory());
        } else {
            $this->assertFunds($project, LedgerAccount::PettyCash->value, $expense->getAmount(), 'amount');
            $this->alerts->pettyCashOutflow($project, $expense->getAmount());
            $movement->addEntry(LedgerAccount::PettyCash, -$expense->getAmount());
            $this->pettyCash->assign($movement);
        }

        $this->em->persist($movement);
        $expense->linkMovement($movement);
    }

    private function assertFunds(Project $project, string $accountKey, int $amount, string $field): void
    {
        $available = $this->ledger->balances($project)->balance($accountKey);
        if ($amount > $available) {
            throw new ApiProblem('insufficient_funds', [
                'available' => MoneyConverter::toMajor($available, $project->getCurrency()),
                'violations' => [$field => ['Fondos insuficientes. Disponible: '.MoneyConverter::toMajor($available, $project->getCurrency()).'.']],
            ]);
        }
    }
}
