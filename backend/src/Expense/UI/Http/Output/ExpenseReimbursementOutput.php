<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class ExpenseReimbursementOutput
{
    public function __construct(
        public int $id,
        #[OA\Property(format: 'date')]
        public ?string $date,
        #[OA\Property(enum: ['TRANSFER', 'CASH', 'CHECK', 'OTHER'])]
        public string $method,
        public ?string $reference,
    ) {
    }
}
