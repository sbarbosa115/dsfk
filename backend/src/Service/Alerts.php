<?php

namespace App\Service;

use App\Entity\Budget;
use App\Entity\Expense;
use App\Entity\LedgerEntry;
use App\Entity\PettyCashCycle;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\LedgerAccount;
use App\Enum\MovementType;
use App\Enum\ProjectRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Decides who gets which email. Must be called before the triggering change is flushed:
 * threshold checks compare the stored state ("before") with the new amount ("after").
 */
class Alerts
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
        private readonly SettingsService $settings,
        private readonly FinancePresenter $finance,
        private readonly LedgerService $ledger,
    ) {
    }

    public function budgetSubmitted(Budget $budget, User $by): void
    {
        $project = $budget->getProject();
        $this->notifier->notify($this->admins(), "Presupuesto enviado a aprobación: {$project->getName()}", 'budget_submitted', ['project' => $project, 'by' => $by]);
    }

    public function budgetReviewed(Budget $budget, User $by, ?string $comment): void
    {
        $project = $budget->getProject();
        $approved = $budget->isApproved();
        $this->notifier->notify(
            [$this->projectManager($project)],
            ($approved ? 'Presupuesto aprobado: ' : 'Presupuesto devuelto: ').$project->getName(),
            'budget_reviewed',
            ['project' => $project, 'by' => $by, 'approved' => $approved, 'comment' => $comment],
        );
    }

    public function expenseSubmitted(Expense $expense): void
    {
        $this->notifier->notify([$this->projectManager($expense->getProject())], 'Gasto por aprobar: '.$expense->getDescription(), 'expense_submitted', $this->expenseContext($expense));
    }

    public function expenseNeedsAdmin(Expense $expense): void
    {
        $this->notifier->notify($this->admins(), 'Gasto sobre el límite por aprobar: '.$expense->getDescription(), 'expense_needs_admin', $this->expenseContext($expense));
    }

    public function expenseRejected(Expense $expense): void
    {
        $this->notifier->notify([$expense->getPaidBy()], 'Gasto rechazado: '.$expense->getDescription(), 'expense_rejected', $this->expenseContext($expense));
    }

    /**
     * @param list<Expense> $expenses
     */
    public function reimbursed(array $expenses): void
    {
        $byUser = [];
        foreach ($expenses as $expense) {
            $byUser[$expense->getPaidBy()->getId()][] = $expense;
        }
        foreach ($byUser as $items) {
            $total = array_sum(array_map(static fn (Expense $e) => $e->getAmount(), $items));
            $project = $items[0]->getProject();
            $this->notifier->notify([$items[0]->getPaidBy()], 'Te reembolsaron gastos de '.$project->getName(), 'reimbursed', [
                'project' => $project,
                'expenses' => $items,
                'total' => $this->money($total, $project),
                'currency' => $project->getCurrency(),
            ]);
        }
    }

    public function cycleClosed(PettyCashCycle $cycle): void
    {
        $project = $cycle->getProject();
        $this->notifier->notify($this->admins(), "Caja menor ciclo {$cycle->getNumber()} cerrado: {$project->getName()}", 'cycle_closed', [
            'project' => $project,
            'cycle' => $cycle,
            'balance' => $this->money($cycle->getClosingBalance() ?? 0, $project),
        ]);
    }

    /**
     * An expense starts counting against the budget: warn when its stage or category
     * crosses one of the configured thresholds (e.g. 80% and 100%).
     */
    public function spendingIncreased(Expense $expense): void
    {
        $project = $expense->getProject();
        $spending = $this->finance->spending($project);
        $amount = $expense->getAmount();

        $stage = $expense->getStage();
        $stageBefore = array_sum($spending[$stage->getId()] ?? []);
        $this->checkThreshold($project, 'la etapa “'.$stage->getName().'”', $stage->getBudgetTotal(), $stageBefore, $stageBefore + $amount);

        $categoryId = $expense->getCategory()->getId();
        $categoryBudget = 0;
        foreach ($project->getStages() as $s) {
            foreach ($s->getBudgetLines() as $line) {
                if ($line->getCategory()->getId() === $categoryId) {
                    $categoryBudget += $line->getTotal();
                }
            }
        }
        $categoryBefore = array_sum(array_map(static fn (array $byCategory) => $byCategory[$categoryId] ?? 0, $spending));
        $this->checkThreshold($project, 'la categoría “'.$expense->getCategory()->getName().'”', $categoryBudget, $categoryBefore, $categoryBefore + $amount);
    }

    /** Money is about to leave the caja menor: warn when it drops below the low-balance threshold. */
    public function pettyCashOutflow(Project $project, int $amount): void
    {
        $before = $this->ledger->balances($project)->balance(LedgerAccount::PettyCash->value);
        $after = $before - $amount;
        $lastTopUp = $this->lastPettyCashTopUp($project);
        if ($lastTopUp <= 0) {
            return;
        }
        $threshold = intdiv($lastTopUp * (int) $this->settings->get(SettingsService::PETTY_CASH_LOW_BALANCE_PERCENT), 100);
        if ($before >= $threshold && $after < $threshold) {
            $this->notifier->notify([...$this->admins(), $this->projectManager($project)], 'Caja menor baja: '.$project->getName(), 'petty_cash_low', [
                'project' => $project,
                'balance' => $this->money($after, $project),
                'lastTopUp' => $this->money($lastTopUp, $project),
            ]);
        }
    }

    /**
     * @return list<User>
     */
    public function admins(): array
    {
        return $this->users->findBy(['admin' => true, 'active' => true]);
    }

    public function projectManager(Project $project): ?User
    {
        foreach ($project->getMembers() as $member) {
            if (ProjectRole::ProjectManager === $member->getRole()) {
                return $member->getUser();
            }
        }

        return null;
    }

    private function checkThreshold(Project $project, string $what, int $budget, int $before, int $after): void
    {
        if ($budget <= 0) {
            return;
        }
        $percents = (array) $this->settings->get(SettingsService::BUDGET_WARNING_PERCENTS);
        rsort($percents);
        foreach ($percents as $percent) {
            $limit = $budget * $percent;
            // Crossing: below the threshold before, at or above it after. Only the highest crossed one is sent.
            if ($before * 100 < $limit && $after * 100 >= $limit) {
                $this->notifier->notify([...$this->admins(), $this->projectManager($project)], "Alerta de presupuesto ({$percent}%): {$project->getName()}", 'budget_threshold', [
                    'project' => $project,
                    'what' => $what,
                    'percent' => $percent,
                    'spent' => $this->money($after, $project),
                    'budget' => $this->money($budget, $project),
                    'executed' => round($after * 100 / $budget, 1),
                ]);

                return;
            }
        }
    }

    private function lastPettyCashTopUp(Project $project): int
    {
        $entry = $this->em->createQueryBuilder()
            ->select('e')->from(LedgerEntry::class, 'e')->join('e.movement', 'm')
            ->where('e.project = :project')->andWhere('e.account = :account')->andWhere('m.type = :type')->andWhere('m.voidedAt IS NULL')
            ->setParameter('project', $project)->setParameter('account', LedgerAccount::PettyCash)->setParameter('type', MovementType::Deposit)
            ->orderBy('m.date', 'DESC')->addOrderBy('e.id', 'DESC')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        return $entry?->getAmount() ?? 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function expenseContext(Expense $expense): array
    {
        return ['project' => $expense->getProject(), 'expense' => $expense, 'amount' => $this->money($expense->getAmount(), $expense->getProject())];
    }

    private function money(int $minor, Project $project): string
    {
        return MoneyConverter::format($minor, $project->getCurrency());
    }
}
