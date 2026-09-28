<?php

declare(strict_types=1);

namespace App\Reporting\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class DashboardAlertOutput
{
    public function __construct(
        #[OA\Property(enum: ['error', 'warning', 'info'])]
        public string $level,
        #[OA\Property(enum: ['stage_over_budget', 'stage_near_budget', 'stage_delayed', 'milestones_overdue', 'expenses_pending', 'expenses_to_reimburse', 'cycles_unsigned'])]
        public string $code,
        public ?string $stage,
        /** Basis points. */
        public ?int $executed,
        #[OA\Property(format: 'date')]
        public ?string $plannedEnd,
        public ?int $count,
    ) {
    }
}
