<?php

declare(strict_types=1);

namespace App\Reporting\Application\Port;

/** The Finance context. Minor units. */
interface MoneySource
{
    /**
     * @return array{deposited: int, available: int, pettyCash: int}
     */
    public function totals(int $projectId): array;

    /**
     * @return array<string, int> by YYYY-MM
     */
    public function monthlyDeposits(int $projectId, \DateTimeImmutable $from): array;

    public function unsignedCycles(int $projectId): int;
}
