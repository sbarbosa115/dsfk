<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Output;

use App\Identity\Application\Query\Membership;
use OpenApi\Attributes as OA;

final readonly class MembershipOutput
{
    public function __construct(
        public int $projectId,
        public string $projectName,
        #[OA\Property(enum: ['PROJECT_MANAGER', 'TEAM_LEAD'])]
        public string $role,
    ) {
    }

    /**
     * @param list<Membership> $memberships
     *
     * @return list<self>
     */
    public static function list(array $memberships): array
    {
        return array_map(static fn (Membership $m): self => new self($m->projectId, $m->projectName, $m->role), $memberships);
    }
}
