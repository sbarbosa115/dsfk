<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Model;

/** Earned value of one stage. Money in minor units, shares in basis points. */
final readonly class StageFigures
{
    public function __construct(
        public StagePlan $stage,
        public int $spent,
        /** Spent against its budget (can pass 10000). */
        public int $executed,
        /** What should be done by today. */
        public int $plannedProgress,
        public int $earned,
        public int $planned,
        public ?float $cpi,
        public ?float $spi,
        public bool $late,
    ) {
    }
}
