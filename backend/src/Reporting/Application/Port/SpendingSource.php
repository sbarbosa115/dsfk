<?php

declare(strict_types=1);

namespace App\Reporting\Application\Port;

/** The Expense context. Minor units. */
interface SpendingSource
{
    /**
     * @return array<int, int> approved spending by stage id
     */
    public function byStage(int $projectId): array;

    /**
     * @return array<string, int> by YYYY-MM
     */
    public function monthly(int $projectId, \DateTimeImmutable $from): array;

    public function pendingCount(int $projectId): int;

    public function toReimburseCount(int $projectId): int;
}
