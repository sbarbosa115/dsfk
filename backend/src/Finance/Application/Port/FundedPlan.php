<?php

declare(strict_types=1);

namespace App\Finance\Application\Port;

/** What Finance needs from the Planning context: whether the budget is approved, and its stages and categories. */
interface FundedPlan
{
    public function isApproved(int $projectId): bool;

    /**
     * @return list<PlanStage> in their order
     */
    public function stages(int $projectId): array;

    /**
     * @return list<PlanCategory> by name
     */
    public function categories(int $projectId): array;

    /** The approved contingency reserve, minor units. */
    public function contingency(int $projectId): int;
}
