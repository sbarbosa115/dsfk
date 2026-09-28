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
        /** The person, or the person and the Admin who viewed the app as them (audit translations, "actor_via") */
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
