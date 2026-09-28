<?php

declare(strict_types=1);

namespace App\Expense\Application\Port;

/** From Settings (the limit) and Project (its currency). */
interface SpendingLimits
{
    /** Above it, a Team Lead's expense needs an Admin too. Minor units of the project's currency. */
    public function teamLeadLimit(int $projectId): int;

    public function currency(int $projectId): string;
}
