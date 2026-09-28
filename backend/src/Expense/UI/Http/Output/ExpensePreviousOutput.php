<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Output;

use OpenApi\Attributes as OA;

/** What an expense said before its owner corrected it. */
final readonly class ExpensePreviousOutput
{
    public function __construct(
        public string $stage,
        public string $category,
        #[OA\Property(format: 'date')]
        public string $date,
        /** Major units. */
        public string $amount,
        public string $description,
        public ?string $supplier,
        public ?string $invoiceNumber,
    ) {
    }
}
