<?php

declare(strict_types=1);

namespace App\Planning\Application\Query;

/** The plan as other contexts see it (Finance funds its stages and compares money with its budget). */
interface PlanDirectory
{
    public function isApproved(int $projectId): bool;

    /**
     * @return list<StageSummary> in their order
     */
    public function stages(int $projectId): array;

    /**
     * @return list<CategorySummary> by name
     */
    public function categories(int $projectId): array;

    /** Contingency reserve, minor units (0 before a budget is written). */
    public function contingency(int $projectId): int;
}
