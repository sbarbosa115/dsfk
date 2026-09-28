<?php

declare(strict_types=1);

namespace App\Reporting\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class StageHealthOutput
{
    public function __construct(
        public int $id,
        public string $name,
        #[OA\Property(enum: ['PENDING', 'IN_PROGRESS', 'COMPLETED'])]
        public string $status,
        /** Major units. */
        public string $budget,
        public string $spent,
        /** Basis points of the budget spent. */
        public int $executed,
        /** Basis points met. */
        public int $progress,
        /** Basis points that should be met by today. */
        public int $plannedProgress,
        public string $earnedValue,
        public string $plannedValue,
        public ?float $cpi,
        public ?float $spi,
        #[OA\Property(format: 'date')]
        public ?string $plannedStart,
        #[OA\Property(format: 'date')]
        public ?string $plannedEnd,
        #[OA\Property(format: 'date')]
        public ?string $actualStart,
        #[OA\Property(format: 'date')]
        public ?string $actualEnd,
        public bool $delayed,
    ) {
    }
}
