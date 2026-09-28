<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

final readonly class CategorySpendingOutput
{
    public function __construct(
        public int $id,
        public string $name,
        /** Major units. */
        public string $budget,
        /** Major units. */
        public string $spent,
        /** Basis points of the budget spent. */
        public int $executed,
    ) {
    }
}
