<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Output;

final readonly class CategoryTotalOutput
{
    public function __construct(
        public int $categoryId,
        /** Major units budgeted in the category */
        public string $total,
    ) {
    }
}
