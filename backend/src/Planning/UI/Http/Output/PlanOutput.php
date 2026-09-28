<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Output;

use OpenApi\Attributes as OA;

/** A project's plan. Amounts are major-unit strings; weights and progress are basis points (10000 = 100 %). */
final readonly class PlanOutput
{
    /**
     * @param list<StageOutput>          $stages
     * @param list<CategoryOutput>       $categories
     * @param list<PlanIssueOutput>|null $issues
     */
    public function __construct(
        public PlanProjectOutput $project,
        public PlanPermissionsOutput $permissions,
        #[OA\Property(enum: ['DRAFT', 'SUBMITTED', 'RETURNED', 'APPROVED'])]
        public string $budgetStatus,
        /** Project progress: stages weighted by their share of the budget */
        public int $progress,
        public array $stages,
        public array $categories,
        /** null for Team Leads */
        public ?BudgetOutput $budget,
        /** What blocks submitting, while editable; null for Team Leads */
        public ?array $issues,
    ) {
    }
}
