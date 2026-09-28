<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class PlanProjectOutput
{
    public function __construct(
        public int $id,
        public string $name,
        public string $currency,
        #[OA\Property(enum: ['DRAFT', 'ACTIVE', 'COMPLETED', 'ARCHIVED'])]
        public string $status,
    ) {
    }
}
