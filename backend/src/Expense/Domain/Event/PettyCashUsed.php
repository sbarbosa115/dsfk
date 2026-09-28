<?php

declare(strict_types=1);

namespace App\Expense\Domain\Event;

/** Money left petty cash (for the low balance warning). */
final readonly class PettyCashUsed
{
    public function __construct(
        public int $projectId,
        /** What left, minor units: the balance before was the current one plus this. */
        public int $amount,
    ) {
    }
}
