<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

/** Money of the project not yet given to a stage. Major units. */
final readonly class ProjectFundOutput
{
    public function __construct(
        public string $deposited,
        /** Leftovers of closed stages. */
        public string $carriedIn,
        /** Deposited + carried in: everything that came in. */
        public string $received,
        /** Given to stages and the caja menor. */
        public string $assigned,
        public string $balance,
    ) {
    }
}
