<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

final readonly class AddLine
{
    public function __construct(
        public int $stageId,
        public int $categoryId,
        public string $description,
        public string $unit,
        /** Up to 3 decimals, e.g. "12.5". */
        public string $quantity,
        /** Major units of the project's currency, e.g. "35000.50". */
        public string $unitPrice,
    ) {
    }
}
