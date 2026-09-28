<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Planning;

use App\Planning\Application\Query\PlanDirectory;
use App\Planning\Application\Query\StageSchedule;
use App\Reporting\Application\Port\PlanSource;
use App\Reporting\Domain\Model\StagePlan;

final readonly class PlanningPlanSource implements PlanSource
{
    public function __construct(private PlanDirectory $plans)
    {
    }

    public function isApproved(int $projectId): bool
    {
        return $this->plans->isApproved($projectId);
    }

    public function contingency(int $projectId): int
    {
        return $this->plans->contingency($projectId);
    }

    public function progress(int $projectId): int
    {
        return $this->plans->progress($projectId);
    }

    public function stages(int $projectId): array
    {
        return array_map(static fn (StageSchedule $s): StagePlan => new StagePlan(
            $s->id,
            $s->name,
            $s->status,
            $s->budget,
            $s->progress,
            $s->plannedStart,
            $s->plannedEnd,
            $s->actualStart,
            $s->actualEnd,
            $s->milestones,
        ), $this->plans->schedule($projectId));
    }
}
