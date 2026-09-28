<?php

declare(strict_types=1);

namespace App\Reporting\UI\Http\Output;

final readonly class MonthFlowOutput
{
    public function __construct(
        /** YYYY-MM */
        public string $month,
        /** Major units. */
        public string $deposited,
        public string $spent,
    ) {
    }
}
