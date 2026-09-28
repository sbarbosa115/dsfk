<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

/** Major units. */
final readonly class ContingencyOutput
{
    public function __construct(
        public string $budgeted,
        public string $deposited,
        /** Leftover of the last stage. */
        public string $carriedIn,
        public string $drawn,
        public string $balance,
    ) {
    }
}
