<?php

declare(strict_types=1);

namespace App\Project\UI\Http\Output;

use App\Project\Domain\Model\ProjectStatus;
use OpenApi\Attributes as OA;

final readonly class ProjectOutput
{
    /**
     * @param list<MemberOutput> $members
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public string $currency,
        public ProjectStatus $status,
        /** YYYY-MM-DD */
        #[OA\Property(format: 'date')]
        public ?string $plannedStart,
        /** YYYY-MM-DD */
        #[OA\Property(format: 'date')]
        public ?string $plannedEnd,
        public \DateTimeImmutable $createdAt,
        /** The signed-in user's role here: ADMIN (global), PROJECT_MANAGER or TEAM_LEAD */
        #[OA\Property(enum: ['ADMIN', 'PROJECT_MANAGER', 'TEAM_LEAD'])]
        public string $myRole,
        public array $members,
    ) {
    }
}
