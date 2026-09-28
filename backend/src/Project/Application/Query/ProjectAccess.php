<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

/**
 * Who may do what in a project, for every context: an Admin can do everything, a member what their role
 * allows, anyone else nothing (and must not learn the project exists).
 */
interface ProjectAccess
{
    public const ADMIN = 'ADMIN';

    /**
     * @return 'ADMIN'|'PROJECT_MANAGER'|'TEAM_LEAD'|null null when the project does not exist or the person
     *                                                    is not in it
     */
    public function roleOf(int $projectId, int $userId, bool $isAdmin): ?string;
}
