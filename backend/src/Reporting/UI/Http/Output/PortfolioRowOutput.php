<?php

declare(strict_types=1);

namespace App\Reporting\UI\Http\Output;

use OpenApi\Attributes as OA;

/** One project on the portfolio dashboard. Money in major units, shares in basis points. */
final readonly class PortfolioRowOutput
{
    public function __construct(
        public int $id,
        public string $name,
        #[OA\Property(enum: ['DRAFT', 'ACTIVE', 'COMPLETED', 'ARCHIVED'])]
        public string $status,
        public string $currency,
        public bool $budgetApproved,
        public string $budget,
        public string $deposited,
        public string $spent,
        public int $progress,
        public int $plannedProgress,
        public int $executed,
        public ?float $cpi,
        public ?float $spi,
        /** Warnings and errors (not the informative ones). */
        public int $alerts,
    ) {
    }
}
