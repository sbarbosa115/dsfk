<?php

declare(strict_types=1);

namespace App\Planning\Infrastructure\Persistence;

use App\Planning\Application\Query\CategorySummary;
use App\Planning\Application\Query\PlanDirectory;
use App\Planning\Application\Query\StageSchedule;
use App\Planning\Application\Query\StageSummary;
use App\Planning\Domain\Model\Milestone;
use App\Planning\Domain\Model\Stage;
use App\Planning\Domain\Repository\PlanRepository;
use App\Planning\Domain\Service\Progress;

final readonly class DoctrinePlanDirectory implements PlanDirectory
{
    public function __construct(private PlanRepository $plans)
    {
    }

    public function isApproved(int $projectId): bool
    {
        return $this->plans->findBudget($projectId)?->isApproved() ?? false;
    }

    public function stages(int $projectId): array
    {
        return array_map(
            static fn (Stage $s): StageSummary => new StageSummary((int) $s->getId(), $s->getName(), $s->getStatus()->value, $s->budgetTotal()),
            $this->plans->stagesOf($projectId),
        );
    }

    public function categories(int $projectId): array
    {
        $budget = [];
        foreach ($this->plans->stagesOf($projectId) as $stage) {
            foreach ($stage->getLines() as $line) {
                $id = (int) $line->getCategory()->getId();
                $budget[$id] = ($budget[$id] ?? 0) + $line->getTotal();
            }
        }
        $categories = [];
        foreach ($this->plans->categoriesOf($projectId) as $category) {
            $id = (int) $category->getId();
            $categories[] = new CategorySummary($id, $category->getName(), $budget[$id] ?? 0);
        }

        return $categories;
    }

    public function schedule(int $projectId): array
    {
        return array_map(static fn (Stage $s): StageSchedule => new StageSchedule(
            (int) $s->getId(),
            $s->getName(),
            $s->getStatus()->value,
            $s->budgetTotal(),
            $s->progress(),
            $s->getPlannedStart(),
            $s->getPlannedEnd(),
            $s->getActualStart(),
            $s->getActualEnd(),
            array_map(static fn (Milestone $m): array => ['weight' => $m->getWeight(), 'plannedDate' => $m->getPlannedDate(), 'completed' => $m->isCompleted()], $s->getMilestones()),
        ), $this->plans->stagesOf($projectId));
    }

    public function progress(int $projectId): int
    {
        return Progress::ofProject($this->plans->stagesOf($projectId));
    }

    public function contingency(int $projectId): int
    {
        return $this->plans->findBudget($projectId)?->getContingency() ?? 0;
    }
}
