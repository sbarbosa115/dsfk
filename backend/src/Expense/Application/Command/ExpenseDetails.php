<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

/** What a person types for an expense (recording or correcting it). */
final readonly class ExpenseDetails
{
    public function __construct(
        public int $stageId,
        public int $categoryId,
        public \DateTimeImmutable $date,
        /** Major units. */
        public string $amount,
        public string $description,
        public ?string $supplier,
        public ?string $invoiceNumber,
    ) {
    }
}
