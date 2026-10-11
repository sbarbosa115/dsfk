<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Output;

use OpenApi\Attributes as OA;

/** An addition to the approved budget (Adición): its new stages and where it stands. */
final readonly class AmendmentOutput
{
    /**
     * @param list<string> $stages
     */
    public function __construct(
        public int $number,
        #[OA\Property(enum: ['DRAFT', 'SUBMITTED', 'RETURNED', 'APPROVED'])]
        public string $status,
        /** Names of its stages, in order. */
        public array $stages,
        /** Major units: the sum of its stages. */
        public string $total,
        public ?\DateTimeImmutable $approvedAt,
    ) {
    }
}
