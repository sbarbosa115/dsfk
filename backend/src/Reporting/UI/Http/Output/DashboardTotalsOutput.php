<?php

declare(strict_types=1);

namespace App\Reporting\UI\Http\Output;

/** Major units. */
final readonly class DashboardTotalsOutput
{
    public function __construct(
        /** The stages' budget (BAC), contingency apart. */
        public string $budget,
        public string $contingency,
        public string $deposited,
        public string $spent,
        /** Still held in every account. */
        public string $available,
        public string $pettyCash,
    ) {
    }
}
