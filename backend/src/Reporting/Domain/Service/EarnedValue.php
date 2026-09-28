<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Service;

use App\Reporting\Domain\Model\ProjectFigures;
use App\Reporting\Domain\Model\StageFigures;
use App\Reporting\Domain\Model\StagePlan;
use App\Shared\Domain\Money\MinorUnits;

/** Earned-value figures of a project from its stages, its spending per stage and the day. */
final class EarnedValue
{
    /**
     * @param list<StagePlan> $stages
     * @param array<int, int> $spentByStage minor units by stage id
     */
    public static function of(array $stages, array $spentByStage, \DateTimeImmutable $today): ProjectFigures
    {
        $budget = 0;
        $earned = 0;
        $planned = 0;
        $spent = 0;
        $figures = [];
        foreach ($stages as $stage) {
            $stageSpent = $spentByStage[$stage->id] ?? 0;
            $share = self::plannedShare($stage, $today);
            $stageEarned = intdiv($stage->budget * $stage->progress, 10000);
            $stagePlanned = intdiv($stage->budget * $share, 10000);
            $budget += $stage->budget;
            $earned += $stageEarned;
            $planned += $stagePlanned;
            $spent += $stageSpent;
            $figures[] = new StageFigures(
                $stage,
                $stageSpent,
                MinorUnits::basisPoints($stageSpent, $stage->budget),
                $share,
                $stageEarned,
                $stagePlanned,
                self::ratio($stageEarned, $stageSpent),
                self::ratio($stageEarned, $stagePlanned),
                self::isLate($stage, $today),
            );
        }
        $forecast = $earned > 0 && $spent > 0 ? (int) round($budget * $spent / $earned) : null;

        return new ProjectFigures(
            $budget,
            $earned,
            $planned,
            $spent,
            self::ratio($earned, $spent),
            self::ratio($earned, $planned),
            $forecast,
            null === $forecast ? null : $budget - $forecast,
            MinorUnits::basisPoints($planned, $budget),
            MinorUnits::basisPoints($spent, $budget),
            $figures,
        );
    }

    /**
     * Share of the stage (basis points) that should be done by the day: from the milestones' planned dates when
     * every milestone has one, otherwise linear between the stage's planned dates.
     */
    public static function plannedShare(StagePlan $stage, \DateTimeImmutable $today): int
    {
        $day = $today->format('Y-m-d');
        $dated = [] !== $stage->milestones && [] === array_filter($stage->milestones, static fn (array $m): bool => null === $m['plannedDate']);
        if ($dated) {
            $due = 0;
            foreach ($stage->milestones as $milestone) {
                if (null !== $milestone['plannedDate'] && $milestone['plannedDate']->format('Y-m-d') <= $day) {
                    $due += $milestone['weight'];
                }
            }

            return min(10000, $due);
        }
        if (null === $stage->plannedStart || null === $stage->plannedEnd) {
            return 0;
        }
        $start = $stage->plannedStart->format('Y-m-d');
        $end = $stage->plannedEnd->format('Y-m-d');
        if ($day >= $end) {
            return 10000;
        }
        if ($day <= $start) {
            return 0;
        }
        $days = static fn (string $from, string $to): int => (int) (new \DateTimeImmutable($from))->diff(new \DateTimeImmutable($to))->days;

        return intdiv($days($start, $day) * 10000, max(1, $days($start, $end)));
    }

    /** Past its planned end and still open, or finished after it. */
    public static function isLate(StagePlan $stage, \DateTimeImmutable $today): bool
    {
        if (null === $stage->plannedEnd) {
            return false;
        }
        $end = $stage->plannedEnd->format('Y-m-d');
        if ($stage->isCompleted()) {
            return null !== $stage->actualEnd && $stage->actualEnd->format('Y-m-d') > $end;
        }

        return $today->format('Y-m-d') > $end;
    }

    private static function ratio(int $a, int $b): ?float
    {
        return $b > 0 ? round($a / $b, 2) : null;
    }
}
