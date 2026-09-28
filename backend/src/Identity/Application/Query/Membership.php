<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

final readonly class Membership
{
    public function __construct(
        public int $projectId,
        public string $projectName,
        /** PROJECT_MANAGER or TEAM_LEAD */
        public string $role,
    ) {
    }
}
