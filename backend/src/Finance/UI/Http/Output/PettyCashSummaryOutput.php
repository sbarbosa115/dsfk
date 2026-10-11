<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

/** Major units. */
final readonly class PettyCashSummaryOutput
{
    public function __construct(
        public string $deposited,
        /** Everything that came in: deposits and fund assignments. */
        public string $received,
        public string $balance,
    ) {
    }
}
