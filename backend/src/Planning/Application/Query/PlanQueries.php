<?php

declare(strict_types=1);

namespace App\Planning\Application\Query;

/** The project a plan part belongs to, so a controller checks access before anything else. null: no such part. */
interface PlanQueries
{
    public function projectOfStage(int $stageId): ?int;

    public function projectOfLine(int $lineId): ?int;

    public function projectOfMilestone(int $milestoneId): ?int;

    public function projectOfCategory(int $categoryId): ?int;
}
