<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class StageOutput
{
    /**
     * @param list<MilestoneOutput> $milestones
     * @param list<LineOutput>|null $lines
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $position,
        #[OA\Property(enum: ['PENDING', 'IN_PROGRESS', 'COMPLETED'])]
        public string $status,
        #[OA\Property(format: 'date')]
        public ?string $plannedStart,
        #[OA\Property(format: 'date')]
        public ?string $plannedEnd,
        #[OA\Property(format: 'date')]
        public ?string $actualStart,
        #[OA\Property(format: 'date')]
        public ?string $actualEnd,
        /** Basis points met */
        public int $progress,
        /** Basis points: must be 10000 to submit */
        public int $milestoneWeightTotal,
        public array $milestones,
        /** Major units; null for Team Leads */
        public ?string $budgetTotal,
        /** Share of the budget in basis points; null for Team Leads */
        public ?int $weight,
        /** null for Team Leads */
        public ?array $lines,
    ) {
    }
}
