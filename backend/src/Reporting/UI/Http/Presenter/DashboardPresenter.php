<?php

declare(strict_types=1);

namespace App\Reporting\UI\Http\Presenter;

use App\Reporting\Application\Service\DashboardAlert;
use App\Reporting\Application\Service\ProjectReport;
use App\Reporting\Domain\Model\StageFigures;
use App\Reporting\UI\Http\Output\DashboardAlertOutput;
use App\Reporting\UI\Http\Output\DashboardTotalsOutput;
use App\Reporting\UI\Http\Output\MonthFlowOutput;
use App\Reporting\UI\Http\Output\PortfolioRowOutput;
use App\Reporting\UI\Http\Output\ProjectDashboardOutput;
use App\Reporting\UI\Http\Output\StageHealthOutput;
use App\Shared\Domain\Money\MinorUnits;

final class DashboardPresenter
{
    public static function project(ProjectReport $r): ProjectDashboardOutput
    {
        $money = static fn (int $minor): string => MinorUnits::toMajor($minor, $r->currency);
        $f = $r->figures;
        $date = static fn (?\DateTimeImmutable $d): ?string => $d?->format('Y-m-d');

        return new ProjectDashboardOutput(
            $r->currency,
            $r->approved,
            new DashboardTotalsOutput($money($f->budget), $money($r->contingency), $money($r->deposited), $money($f->spent), $money($r->available), $money($r->pettyCash)),
            $r->progress,
            $f->plannedProgress,
            $f->executed,
            $money($f->earned),
            $money($f->planned),
            $f->cpi,
            $f->spi,
            null === $f->forecast ? null : $money($f->forecast),
            null === $f->varianceAtCompletion ? null : $money($f->varianceAtCompletion),
            array_map(static fn (StageFigures $s): StageHealthOutput => new StageHealthOutput(
                $s->stage->id,
                $s->stage->name,
                $s->stage->status,
                $money($s->stage->budget),
                $money($s->spent),
                $s->executed,
                $s->stage->progress,
                $s->plannedProgress,
                $money($s->earned),
                $money($s->planned),
                $s->cpi,
                $s->spi,
                $date($s->stage->plannedStart),
                $date($s->stage->plannedEnd),
                $date($s->stage->actualStart),
                $date($s->stage->actualEnd),
                $s->late,
            ), $f->stages),
            array_map(static fn (array $m): MonthFlowOutput => new MonthFlowOutput($m['month'], $money($m['deposited']), $money($m['spent'])), $r->monthly),
            array_map(static fn (DashboardAlert $a): DashboardAlertOutput => new DashboardAlertOutput($a->level, $a->code, $a->stage, $a->executed, $date($a->plannedEnd), $a->count), $r->alerts),
        );
    }

    public static function row(ProjectReport $r): PortfolioRowOutput
    {
        $money = static fn (int $minor): string => MinorUnits::toMajor($minor, $r->currency);
        $f = $r->figures;

        return new PortfolioRowOutput(
            $r->projectId,
            $r->name,
            $r->status,
            $r->currency,
            $r->approved,
            $money($f->budget),
            $money($r->deposited),
            $money($f->spent),
            $r->progress,
            $f->plannedProgress,
            $f->executed,
            $f->cpi,
            $f->spi,
            \count(array_filter($r->alerts, static fn (DashboardAlert $a): bool => 'info' !== $a->level)),
        );
    }
}
