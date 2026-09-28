<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Expense;

use App\Expense\Application\Query\ExpenseDirectory;
use App\Reporting\Application\Port\SpendingSource;

final readonly class ExpenseSpendingSource implements SpendingSource
{
    public function __construct(private ExpenseDirectory $expenses)
    {
    }

    public function byStage(int $projectId): array
    {
        return array_map(static fn (array $byCategory): int => array_sum($byCategory), $this->expenses->spending($projectId));
    }

    public function monthly(int $projectId, \DateTimeImmutable $from): array
    {
        return $this->expenses->monthlySpending($projectId, $from);
    }

    public function pendingCount(int $projectId): int
    {
        return $this->expenses->pendingCount($projectId);
    }

    public function toReimburseCount(int $projectId): int
    {
        return $this->expenses->toReimburseCount($projectId);
    }
}
