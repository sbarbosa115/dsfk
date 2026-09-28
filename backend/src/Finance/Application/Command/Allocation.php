<?php

declare(strict_types=1);

namespace App\Finance\Application\Command;

use App\Finance\Domain\Model\LedgerAccount;

/** One part of a deposit: how much goes to which account (a stage, optionally earmarked for a category). */
final readonly class Allocation
{
    public function __construct(
        public LedgerAccount $destination,
        /** Major units, e.g. "1500000.00". */
        public string $amount,
        public ?int $stageId = null,
        public ?int $categoryId = null,
    ) {
    }
}
