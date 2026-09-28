<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class MovementOutput
{
    /**
     * @param list<LedgerEntryOutput> $entries
     * @param list<AttachmentOutput>  $attachments
     */
    public function __construct(
        public int $id,
        #[OA\Property(enum: ['DEPOSIT', 'CONTINGENCY_DRAW', 'CARRYOVER', 'EXPENSE', 'REIMBURSEMENT'])]
        public string $type,
        #[OA\Property(format: 'date')]
        public string $date,
        /** Major units, positive. */
        public string $amount,
        #[OA\Property(enum: ['TRANSFER', 'CASH', 'CHECK', 'OTHER'])]
        public ?string $method,
        public ?string $reference,
        public ?string $note,
        public FinancePersonOutput $createdBy,
        #[OA\Property(format: 'date-time')]
        public string $createdAt,
        public ?VoidOutput $voided,
        public array $entries,
        public array $attachments,
    ) {
    }
}
