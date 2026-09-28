<?php

declare(strict_types=1);

namespace App\Finance\Infrastructure\Expense;

use App\Finance\Application\Port\Spending;

/** Until the Expense context exists nothing has been spent. */
final class NoSpendingYet implements Spending
{
    public function byStageAndCategory(int $projectId): array
    {
        return [];
    }
}
