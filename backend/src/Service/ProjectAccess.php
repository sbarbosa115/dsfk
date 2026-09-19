<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectRole;

/**
 * The effective role of a user in a project: ADMIN (global), PROJECT_MANAGER, TEAM_LEAD or null.
 */
class ProjectAccess
{
    public const ADMIN = 'ADMIN';

    public function role(Project $project, User $user): ?string
    {
        if ($user->isAdmin()) {
            return self::ADMIN;
        }

        return $project->findMember($user)?->getRole()->value;
    }

    public function isManager(Project $project, User $user): bool
    {
        return \in_array($this->role($project, $user), [self::ADMIN, ProjectRole::ProjectManager->value], true);
    }
}
