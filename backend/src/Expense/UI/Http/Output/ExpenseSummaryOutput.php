<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Output;

/** Team Lead expenses in numbers (a Team Lead sees their own). Amounts in major units. */
final readonly class ExpenseSummaryOutput
{
    public function __construct(
        /** Waiting for the PM, or for an Admin above the limit. */
        public int $pendingCount,
        public string $pendingTotal,
        /** Approved, not paid back yet: what the project owes its Team Leads. */
        public int $toReimburseCount,
        public string $toReimburseTotal,
        public string $teamLeadLimit,
    ) {
    }
}
