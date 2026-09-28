<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Domain\Model\ProjectRole;

/** Adds a person to a project, or changes their role there. */
final readonly class AssignMember
{
    public function __construct(public int $projectId, public int $userId, public ProjectRole $role)
    {
    }
}
