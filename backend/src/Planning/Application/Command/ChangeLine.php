<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

final readonly class ChangeLine
{
    public function __construct(
        public int $lineId,
        public int $categoryId,
        public string $description,
        public string $unit,
        public string $quantity,
        public string $unitPrice,
    ) {
    }
}
