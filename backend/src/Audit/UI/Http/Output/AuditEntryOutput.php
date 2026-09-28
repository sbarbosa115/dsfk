<?php

declare(strict_types=1);

namespace App\Audit\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class AuditEntryOutput
{
    /**
     * @param array<string, mixed> $changes field => [before, after]
     */
    public function __construct(
        public int $id,
        public ?int $projectId,
        public ?string $projectName,
        /** "Laura Gómez", or "Laura Gómez (vía Ana Admin)" while an Admin viewed the app as her */
        public ?string $user,
        #[OA\Property(enum: ['create', 'update', 'delete'])]
        public string $action,
        /** Project, Stage, Expense, FundMovement… */
        public string $entityType,
        public ?int $entityId,
        #[OA\Property(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items()))]
        public array $changes,
        #[OA\Property(format: 'date-time')]
        public string $createdAt,
    ) {
    }
}
