<?php

declare(strict_types=1);

namespace App\Planning\Application\Query;

final readonly class CategorySummary
{
    public function __construct(
        public int $id,
        public string $name,
        /** Budgeted across the stages, minor units. */
        public int $budget,
    ) {
    }
}
