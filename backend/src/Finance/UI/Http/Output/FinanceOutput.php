<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

/** Where a project's deposited money is (per stage, petty cash, contingency), next to its approved budget. */
final readonly class FinanceOutput
{
    /**
     * @param list<StageFundingOutput>     $stages
     * @param list<CategorySpendingOutput> $categories
     */
    public function __construct(
        public string $currency,
        public bool $budgetApproved,
        public FinancePermissionsOutput $permissions,
        public FinanceTotalsOutput $totals,
        public array $stages,
        public array $categories,
        public ContingencyOutput $contingency,
        public PettyCashSummaryOutput $pettyCash,
    ) {
    }
}
