<?php

declare(strict_types=1);

namespace App\Finance\Application\Port;

final readonly class PlanCategory
{
    public function __construct(
        public int $id,
        public string $name,
        /** Budgeted across the stages, minor units. */
        public int $budget,
    ) {
    }
}
