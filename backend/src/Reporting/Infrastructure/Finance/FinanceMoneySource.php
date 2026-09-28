<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Finance;

use App\Finance\Application\Query\FinanceQueries;
use App\Reporting\Application\Port\MoneySource;

final readonly class FinanceMoneySource implements MoneySource
{
    public function __construct(private FinanceQueries $finance)
    {
    }

    public function totals(int $projectId): array
    {
        return $this->finance->fundingTotals($projectId);
    }

    public function monthlyDeposits(int $projectId, \DateTimeImmutable $from): array
    {
        return $this->finance->monthlyDeposits($projectId, $from);
    }

    public function unsignedCycles(int $projectId): int
    {
        return $this->finance->unsignedCycles($projectId);
    }
}
