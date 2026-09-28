<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class VoidOutput
{
    public function __construct(
        #[OA\Property(format: 'date-time')]
        public string $at,
        public string $by,
        public string $reason,
    ) {
    }
}
