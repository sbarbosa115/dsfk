<?php

declare(strict_types=1);

namespace App\Finance\Application\Port;

/** The project's currency (from the Project context): every amount of its money is in it. */
interface Currencies
{
    public function of(int $projectId): string;
}
