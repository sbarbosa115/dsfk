<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class CycleMovementOutput
{
    /**
     * @param list<AttachmentOutput> $attachments proofs, or the expense's receipts
     */
    public function __construct(
        public int $id,
        #[OA\Property(enum: ['DEPOSIT', 'CONTINGENCY_DRAW', 'CARRYOVER', 'EXPENSE', 'REIMBURSEMENT'])]
        public string $type,
        #[OA\Property(format: 'date')]
        public string $date,
        /** Major units, signed: what it put into (+) or took out of (−) the caja menor. */
        public string $amount,
        public string $description,
        public string $user,
        public bool $voided,
        public array $attachments,
    ) {
    }
}
