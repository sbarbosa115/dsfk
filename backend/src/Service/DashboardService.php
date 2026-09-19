<?php

namespace App\Service;

use App\Entity\Expense;
use App\Entity\FundMovement;
use App\Entity\Milestone;
use App\Entity\PettyCashCycle;
use App\Entity\Project;
use App\Entity\Stage;
use App\Enum\CycleStatus;
use App\Enum\ExpenseStatus;
use App\Enum\LedgerAccount;
use App\Enum\MovementType;
use App\Enum\PaidFrom;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Earned-value view of a project, as of today:
 *  - BAC: approved budget of the stages (contingency excluded)
 *  - EV:  BAC × physical progress (weighted milestones)
 *  - PV:  budget that should have been "earned" by today according to the planned dates
 *  - AC:  approved spending
 *  - CPI = EV / AC (below 1: spending more than the progress justifies)
 *  - SPI = EV / PV (below 1: behind schedule)
 *  - EAC = BAC / CPI (forecast final cost)
 */
class DashboardService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FinancePresenter $finance,
        private readonly LedgerService $ledger,
        private readonly SettingsService $settings,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function project(Project $project, ?\DateTimeImmutable $today = null): array
    {
        $today ??= new \DateTimeImmutable('today');
        $currency = $project->getCurrency();
        $money = static fn (int $minor): string => MoneyConverter::toMajor($minor, $currency);
        $spending = $this->finance->spending($project);
        $balances = $this->ledger->balances($project);

        $bac = 0;
        $ev = 0;
        $pv = 0;
        $ac = 0;
        $stages = [];
        foreach ($project->getStages() as $stage) {
            $budget = $stage->getBudgetTotal();
            $spent = array_sum($spending[$stage->getId()] ?? []);
            $stageEv = intdiv($budget * $stage->getProgress(), 10000);
            $stagePv = intdiv($budget * $this->plannedFraction($stage, $today), 10000);
            $bac += $budget;
            $ev += $stageEv;
            $pv += $stagePv;
            $ac += $spent;

            $stages[] = [
                'id' => $stage->getId(),
                'name' => $stage->getName(),
                'status' => $stage->getStatus()->value,
                'budget' => $money($budget),
                'spent' => $money($spent),
                'executed' => $budget > 0 ? intdiv($spent * 10000, $budget) : 0,
                'progress' => $stage->getProgress(),
                'plannedProgress' => $this->plannedFraction($stage, $today),
                'earnedValue' => $money($stageEv),
                'plannedValue' => $money($stagePv),
                'cpi' => self::ratio($stageEv, $spent),
                'spi' => self::ratio($stageEv, $stagePv),
                'plannedStart' => $stage->getPlannedStart()?->format('Y-m-d'),
                'plannedEnd' => $stage->getPlannedEnd()?->format('Y-m-d'),
                'actualStart' => $stage->getActualStart()?->format('Y-m-d'),
                'actualEnd' => $stage->getActualEnd()?->format('Y-m-d'),
                'delayed' => $this->isDelayed($stage, $today),
            ];
        }

        // Spending not tied to a stage cannot exist, but keep AC honest if stages were reordered/removed.
        $cpi = self::ratio($ev, $ac);
        $eac = null !== $cpi && $cpi > 0 ? (int) round($bac / $cpi) : null;

        return [
            'currency' => $currency,
            'budgetApproved' => $project->getBudget()->isApproved(),
            'totals' => [
                'budget' => $money($bac),
                'contingency' => $money($project->getBudget()->getContingency()),
                'deposited' => $money($this->deposited($balances, $project)),
                'spent' => $money($ac),
                'available' => $money($this->available($balances, $project)),
                'pettyCash' => $money($balances->balance(LedgerAccount::PettyCash->value)),
            ],
            'progress' => PlanPresenter::projectProgress($project, $bac),
            'plannedProgress' => $bac > 0 ? intdiv($pv * 10000, $bac) : 0,
            'executed' => $bac > 0 ? intdiv($ac * 10000, $bac) : 0,
            'earnedValue' => $money($ev),
            'plannedValue' => $money($pv),
            'cpi' => $cpi,
            'spi' => self::ratio($ev, $pv),
            'forecastAtCompletion' => null === $eac ? null : $money($eac),
            'varianceAtCompletion' => null === $eac ? null : $money($bac - $eac),
            'stages' => $stages,
            'monthly' => $this->monthly($project, $today),
            'alerts' => $this->alerts($project, $spending, $balances, $today),
        ];
    }

    /**
     * Compact row for the portfolio view.
     *
     * @return array<string, mixed>
     */
    public function summary(Project $project): array
    {
        $d = $this->project($project);

        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'status' => $project->getStatus()->value,
            'currency' => $project->getCurrency(),
            'plannedEnd' => $project->getPlannedEnd()?->format('Y-m-d'),
            'budgetApproved' => $d['budgetApproved'],
            'budget' => $d['totals']['budget'],
            'deposited' => $d['totals']['deposited'],
            'spent' => $d['totals']['spent'],
            'progress' => $d['progress'],
            'plannedProgress' => $d['plannedProgress'],
            'executed' => $d['executed'],
            'cpi' => $d['cpi'],
            'spi' => $d['spi'],
            'alerts' => \count(array_filter($d['alerts'], static fn (array $a) => 'info' !== $a['level'])),
        ];
    }

    /**
     * Share of the stage (basis points) that should be done by $today: from milestone
     * planned dates when every milestone has one, else linear between the stage's planned dates.
     */
    public function plannedFraction(Stage $stage, \DateTimeImmutable $today): int
    {
        $milestones = $stage->getMilestones();
        $allDated = !$milestones->isEmpty() && $milestones->forAll(static fn ($k, Milestone $m) => null !== $m->getPlannedDate());
        if ($allDated) {
            $due = 0;
            foreach ($milestones as $milestone) {
                if ($milestone->getPlannedDate() <= $today) {
                    $due += $milestone->getWeight();
                }
            }

            return min(10000, $due);
        }

        $start = $stage->getPlannedStart();
        $end = $stage->getPlannedEnd();
        if (null === $start || null === $end) {
            return 0;
        }
        if ($today >= $end) {
            return 10000;
        }
        if ($today <= $start) {
            return 0;
        }
        $total = max(1, $end->getTimestamp() - $start->getTimestamp());

        return intdiv(($today->getTimestamp() - $start->getTimestamp()) * 10000, $total);
    }

    private function isDelayed(Stage $stage, \DateTimeImmutable $today): bool
    {
        $end = $stage->getPlannedEnd();
        if (null === $end) {
            return false;
        }

        return $stage->isCompleted() ? $stage->getActualEnd() > $end : $today > $end;
    }

    private static function ratio(int $a, int $b): ?float
    {
        return $b > 0 ? round($a / $b, 2) : null;
    }

    private function deposited(Balances $b, Project $project): int
    {
        $total = $b->in(LedgerAccount::PettyCash->value, MovementType::Deposit) + $b->in(LedgerAccount::Contingency->value, MovementType::Deposit);
        foreach ($project->getStages() as $stage) {
            $total += $b->in(Balances::key(LedgerAccount::Stage, $stage->getId()), MovementType::Deposit);
        }

        return $total;
    }

    private function available(Balances $b, Project $project): int
    {
        $total = $b->balance(LedgerAccount::PettyCash->value) + $b->balance(LedgerAccount::Contingency->value);
        foreach ($project->getStages() as $stage) {
            $total += $b->stage($stage->getId());
        }

        return $total;
    }

    /**
     * Deposits and approved spending per month, last 12 months including the current one.
     *
     * @return list<array{month: string, deposited: string, spent: string}>
     */
    private function monthly(Project $project, \DateTimeImmutable $today): array
    {
        $from = $today->modify('first day of this month')->modify('-11 months');
        $months = [];
        for ($m = $from; $m <= $today; $m = $m->modify('+1 month')) {
            $months[$m->format('Y-m')] = ['deposited' => 0, 'spent' => 0];
        }

        $expenses = $this->em->createQueryBuilder()
            ->select('e.date AS date', 'e.amount AS amount')->from(Expense::class, 'e')
            ->where('e.project = :project')->andWhere('e.status IN (:spent)')->andWhere('e.date >= :from')
            ->setParameter('project', $project)->setParameter('spent', [ExpenseStatus::Approved, ExpenseStatus::Reimbursed])->setParameter('from', $from)
            ->getQuery()->getArrayResult();
        foreach ($expenses as $row) {
            $key = $row['date']->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]['spent'] += (int) $row['amount'];
            }
        }

        $deposits = $this->em->createQueryBuilder()
            ->select('m.date AS date', 'm.amount AS amount')->from(FundMovement::class, 'm')
            ->where('m.project = :project')->andWhere('m.type = :type')->andWhere('m.voidedAt IS NULL')->andWhere('m.date >= :from')
            ->setParameter('project', $project)->setParameter('type', MovementType::Deposit)->setParameter('from', $from)
            ->getQuery()->getArrayResult();
        foreach ($deposits as $row) {
            $key = $row['date']->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]['deposited'] += (int) $row['amount'];
            }
        }

        $result = [];
        foreach ($months as $month => $values) {
            $result[] = [
                'month' => $month,
                'deposited' => MoneyConverter::toMajor($values['deposited'], $project->getCurrency()),
                'spent' => MoneyConverter::toMajor($values['spent'], $project->getCurrency()),
            ];
        }

        return $result;
    }

    /**
     * Things that need attention. Codes are translated by the UI.
     *
     * @param array<int, array<int, int>> $spending
     *
     * @return list<array{level: string, code: string, params: array<string, mixed>}>
     */
    private function alerts(Project $project, array $spending, Balances $balances, \DateTimeImmutable $today): array
    {
        $alerts = [];
        $percents = (array) $this->settings->get(SettingsService::BUDGET_WARNING_PERCENTS);
        $warnAt = [] === $percents ? 8000 : min($percents) * 100;

        foreach ($project->getStages() as $stage) {
            $budget = $stage->getBudgetTotal();
            $spent = array_sum($spending[$stage->getId()] ?? []);
            if ($budget > 0 && $spent >= $budget) {
                $alerts[] = ['level' => 'error', 'code' => 'stage_over_budget', 'params' => ['stage' => $stage->getName(), 'executed' => intdiv($spent * 10000, $budget)]];
            } elseif ($budget > 0 && $spent * 10000 >= $budget * $warnAt) {
                $alerts[] = ['level' => 'warning', 'code' => 'stage_near_budget', 'params' => ['stage' => $stage->getName(), 'executed' => intdiv($spent * 10000, $budget)]];
            }
            if ($this->isDelayed($stage, $today) && !$stage->isCompleted()) {
                $alerts[] = ['level' => 'warning', 'code' => 'stage_delayed', 'params' => ['stage' => $stage->getName(), 'plannedEnd' => $stage->getPlannedEnd()->format('Y-m-d')]];
            }
        }

        $overdue = (int) $this->em->createQueryBuilder()
            ->select('COUNT(m.id)')->from(Milestone::class, 'm')->join('m.stage', 's')
            ->where('s.project = :project')->andWhere('m.completedAt IS NULL')->andWhere('m.plannedDate < :today')
            ->setParameter('project', $project)->setParameter('today', $today)
            ->getQuery()->getSingleScalarResult();
        if ($overdue > 0) {
            $alerts[] = ['level' => 'warning', 'code' => 'milestones_overdue', 'params' => ['count' => $overdue]];
        }

        $pending = $this->em->getRepository(Expense::class)->count(['project' => $project, 'status' => [ExpenseStatus::Submitted, ExpenseStatus::PmApproved]]);
        if ($pending > 0) {
            $alerts[] = ['level' => 'info', 'code' => 'expenses_pending', 'params' => ['count' => $pending]];
        }
        $toReimburse = $this->em->getRepository(Expense::class)->count(['project' => $project, 'status' => ExpenseStatus::Approved, 'paidFrom' => PaidFrom::OutOfPocket]);
        if ($toReimburse > 0) {
            $alerts[] = ['level' => 'info', 'code' => 'expenses_to_reimburse', 'params' => ['count' => $toReimburse]];
        }
        $unsigned = $this->em->getRepository(PettyCashCycle::class)->count(['project' => $project, 'status' => CycleStatus::Closed]);
        if ($unsigned > 0) {
            $alerts[] = ['level' => 'info', 'code' => 'cycles_unsigned', 'params' => ['count' => $unsigned]];
        }

        return $alerts;
    }
}
