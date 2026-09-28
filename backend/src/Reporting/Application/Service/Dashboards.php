<?php

declare(strict_types=1);

namespace App\Reporting\Application\Service;

use App\Reporting\Application\Port\MoneySource;
use App\Reporting\Application\Port\PlanSource;
use App\Reporting\Application\Port\ProjectSource;
use App\Reporting\Application\Port\SpendingSource;
use App\Reporting\Application\Port\WarningThresholds;
use App\Reporting\Domain\Model\ProjectFigures;
use App\Reporting\Domain\Service\EarnedValue;
use Psr\Clock\ClockInterface;

/** The read side the dashboards show: earned value, money in and out per month, and what needs attention. */
final readonly class Dashboards
{
    private const MONTHS = 12;

    public function __construct(
        private ProjectSource $projects,
        private PlanSource $plan,
        private MoneySource $money,
        private SpendingSource $spending,
        private WarningThresholds $thresholds,
        private ClockInterface $clock,
    ) {
    }

    public function project(int $projectId): ProjectReport
    {
        $today = $this->clock->now();
        $info = $this->projects->info($projectId);
        $stages = $this->plan->stages($projectId);
        $figures = EarnedValue::of($stages, $this->spending->byStage($projectId), $today);
        $totals = $this->money->totals($projectId);

        return new ProjectReport(
            $projectId,
            $info['name'],
            $info['status'],
            $info['currency'],
            $this->plan->isApproved($projectId),
            $this->plan->contingency($projectId),
            $totals['deposited'],
            $totals['available'],
            $totals['pettyCash'],
            $this->plan->progress($projectId),
            $figures,
            $this->monthly($projectId, $today),
            $this->alerts($projectId, $figures, $today),
        );
    }

    /**
     * @param list<int> $projectIds
     *
     * @return list<ProjectReport>
     */
    public function portfolio(array $projectIds): array
    {
        return array_map($this->project(...), $projectIds);
    }

    /**
     * Deposits and approved spending per month: the last twelve, this one included.
     *
     * @return list<array{month: string, deposited: int, spent: int}>
     */
    private function monthly(int $projectId, \DateTimeImmutable $today): array
    {
        $from = $today->modify('first day of this month')->modify('-'.(self::MONTHS - 1).' months')->setTime(0, 0);
        $deposits = $this->money->monthlyDeposits($projectId, $from);
        $spent = $this->spending->monthly($projectId, $from);
        $months = [];
        for ($m = $from, $i = 0; $i < self::MONTHS; $m = $m->modify('+1 month'), ++$i) {
            $key = $m->format('Y-m');
            $months[] = ['month' => $key, 'deposited' => $deposits[$key] ?? 0, 'spent' => $spent[$key] ?? 0];
        }

        return $months;
    }

    /**
     * @return list<DashboardAlert>
     */
    private function alerts(int $projectId, ProjectFigures $figures, \DateTimeImmutable $today): array
    {
        $percents = $this->thresholds->budgetPercents();
        $warnAt = ([] === $percents ? 80 : min($percents)) * 100;
        $alerts = [];
        $overdue = 0;
        foreach ($figures->stages as $s) {
            if ($s->stage->budget > 0 && $s->executed >= 10000) {
                $alerts[] = new DashboardAlert('error', 'stage_over_budget', stage: $s->stage->name, executed: $s->executed);
            } elseif ($s->stage->budget > 0 && $s->executed >= $warnAt) {
                $alerts[] = new DashboardAlert('warning', 'stage_near_budget', stage: $s->stage->name, executed: $s->executed);
            }
            if ($s->late && !$s->stage->isCompleted()) {
                $alerts[] = new DashboardAlert('warning', 'stage_delayed', stage: $s->stage->name, plannedEnd: $s->stage->plannedEnd);
            }
            foreach ($s->stage->milestones as $m) {
                if (!$m['completed'] && null !== $m['plannedDate'] && $m['plannedDate']->format('Y-m-d') < $today->format('Y-m-d')) {
                    ++$overdue;
                }
            }
        }
        if ($overdue > 0) {
            $alerts[] = new DashboardAlert('warning', 'milestones_overdue', count: $overdue);
        }
        foreach ([
            'expenses_pending' => $this->spending->pendingCount($projectId),
            'expenses_to_reimburse' => $this->spending->toReimburseCount($projectId),
            'cycles_unsigned' => $this->money->unsignedCycles($projectId),
        ] as $code => $count) {
            if ($count > 0) {
                $alerts[] = new DashboardAlert('info', $code, count: $count);
            }
        }

        return $alerts;
    }
}
