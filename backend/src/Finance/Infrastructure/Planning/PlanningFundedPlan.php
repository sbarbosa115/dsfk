<?php

declare(strict_types=1);

namespace App\Finance\Infrastructure\Planning;

use App\Finance\Application\Port\FundedPlan;
use App\Finance\Application\Port\PlanCategory;
use App\Finance\Application\Port\PlanStage;
use App\Planning\Application\Query\PlanDirectory;

final readonly class PlanningFundedPlan implements FundedPlan
{
    public function __construct(private PlanDirectory $plans)
    {
    }

    public function isApproved(int $projectId): bool
    {
        return $this->plans->isApproved($projectId);
    }

    public function stages(int $projectId): array
    {
        return array_map(static fn ($s): PlanStage => new PlanStage($s->id, $s->name, $s->status, $s->budget), $this->plans->stages($projectId));
    }

    public function categories(int $projectId): array
    {
        return array_map(static fn ($c): PlanCategory => new PlanCategory($c->id, $c->name, $c->budget), $this->plans->categories($projectId));
    }

    public function contingency(int $projectId): int
    {
        return $this->plans->contingency($projectId);
    }
}
