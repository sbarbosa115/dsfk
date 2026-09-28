<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

use OpenApi\Attributes as OA;

/** A petty cash period: opening balance, top-ups, spending, reimbursements and closing balance (major units). */
final readonly class CycleOutput
{
    /**
     * @param list<CycleMovementOutput>|null $movements null in lists
     */
    public function __construct(
        /** null for a cycle that opens with the next use of petty cash */
        public ?int $id,
        public int $number,
        #[OA\Property(enum: ['OPEN', 'CLOSED', 'SIGNED_OFF'])]
        public string $status,
        #[OA\Property(format: 'date-time')]
        public ?string $openedAt,
        #[OA\Property(format: 'date-time')]
        public ?string $closedAt,
        public ?string $closedBy,
        public ?string $closingNote,
        #[OA\Property(format: 'date-time')]
        public ?string $signedOffAt,
        public ?string $signedOffBy,
        public string $openingBalance,
        public string $topUps,
        public string $spent,
        public string $reimbursed,
        /** Snapshot when closed; the running balance while open. */
        public string $closingBalance,
        public ?array $movements,
    ) {
    }
}
