<?php

declare(strict_types=1);

namespace App\Planning\Infrastructure\Finance;

use App\Expense\Application\Query\ExpenseDirectory;
use App\Finance\Application\Query\FinanceQueries;
use App\Planning\Application\Port\StageUsage;

/** A stage stays while a ledger entry (a deposit's part, an assignment, a draw) or an expense refers to it. */
final readonly class MoneyStageUsage implements StageUsage
{
    public function __construct(private FinanceQueries $finance, private ExpenseDirectory $expenses)
    {
    }

    public function usedStages(int $projectId): array
    {
        return array_values(array_unique([...$this->finance->stagesWithMoney($projectId), ...$this->expenses->stagesWithExpenses($projectId)]));
    }
}
