<?php

declare(strict_types=1);

namespace App\Finance\Infrastructure\Expense;

use App\Expense\Application\Query\ExpenseDirectory;
use App\Finance\Application\Port\Spending;

final readonly class ExpenseSpending implements Spending
{
    public function __construct(private ExpenseDirectory $expenses)
    {
    }

    public function byStageAndCategory(int $projectId): array
    {
        return $this->expenses->spending($projectId);
    }
}
