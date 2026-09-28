<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

use OpenApi\Attributes as OA;

/** Money of one stage, major units. */
final readonly class StageFundingOutput
{
    public function __construct(
        public int $id,
        public string $name,
        #[OA\Property(enum: ['PENDING', 'IN_PROGRESS', 'COMPLETED'])]
        public string $status,
        public string $budget,
        public string $deposited,
        public string $contingencyDraws,
        public string $carriedIn,
        public string $carriedOut,
        /** Deposited + draws + carried in. */
        public string $received,
        public string $available,
        /** Received above the budget. */
        public string $beyondBudget,
        public string $spent,
        public string $remainingBudget,
        /** Basis points of the budget spent (can pass 10000). */
        public int $executed,
        /** Basis points of the budget received (can pass 10000). */
        public int $funded,
        /** Where its leftover goes when completed; null when completed or last. */
        public ?string $nextStage,
    ) {
    }
}
