<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

/** Major units. */
final readonly class FinanceTotalsOutput
{
    public function __construct(
        /** Stages plus contingency. */
        public string $budget,
        public string $deposited,
        public string $spent,
        public string $stagesAvailable,
        public string $pettyCash,
        public string $contingency,
    ) {
    }
}
