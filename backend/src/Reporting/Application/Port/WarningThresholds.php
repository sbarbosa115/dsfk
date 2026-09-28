<?php

declare(strict_types=1);

namespace App\Reporting\Application\Port;

/** From Settings: the budget percentages that raise a warning (e.g. 80 and 100). */
interface WarningThresholds
{
    /**
     * @return list<int> ascending
     */
    public function budgetPercents(): array;
}
