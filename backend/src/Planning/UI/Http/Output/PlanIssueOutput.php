<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Output;

use OpenApi\Attributes as OA;

/** Why the budget cannot be submitted yet. */
final readonly class PlanIssueOutput
{
    public function __construct(
        #[OA\Property(enum: ['no_stages', 'stage_without_lines', 'milestone_weights'])]
        public string $code,
        public ?int $stageId,
    ) {
    }
}
