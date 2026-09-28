<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Output;

final readonly class LineOutput
{
    public function __construct(
        public int $id,
        public int $categoryId,
        public string $description,
        public string $unit,
        /** "12.5" */
        public string $quantity,
        /** Major units */
        public string $unitPrice,
        /** Major units: quantity × unit price, rounded half up */
        public string $total,
    ) {
    }
}
