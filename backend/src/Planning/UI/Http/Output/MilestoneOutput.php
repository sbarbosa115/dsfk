<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class MilestoneOutput
{
    public function __construct(
        public int $id,
        public string $name,
        /** Basis points of the stage (10000 = 100 %) */
        public int $weight,
        #[OA\Property(format: 'date')]
        public ?string $plannedDate,
        #[OA\Property(format: 'date')]
        public ?string $completedAt,
        public ?PersonOutput $completedBy,
        public ?string $completionNotes,
        /** Not met and its planned date has gone by */
        public bool $overdue,
    ) {
    }
}
