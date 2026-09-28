<?php

declare(strict_types=1);

namespace App\Finance\Application\Port;

/** Spending that counts against the budget (approved and reimbursed expenses), from the Expense context. */
interface Spending
{
    /**
     * @return array<int, array<int, int>> stage id => category id => minor units
     */
    public function byStageAndCategory(int $projectId): array;
}
