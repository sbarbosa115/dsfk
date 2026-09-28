<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class ExpenseEventOutput
{
    public function __construct(
        #[OA\Property(enum: ['CREATED', 'EDITED', 'PM_APPROVED', 'APPROVED', 'REJECTED', 'REIMBURSED', 'VOIDED'])]
        public string $type,
        public string $user,
        public ?string $comment,
        public ?ExpensePreviousOutput $previous,
        #[OA\Property(format: 'date-time')]
        public string $createdAt,
    ) {
    }
}
