<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Shared\Domain\Error\NotFound;

/** Projects as other contexts see them (currency, name, status, the Project Manager). */
interface ProjectDirectory
{
    /**
     * @throws NotFound project_not_found
     */
    public function info(int $projectId): ProjectInfo;

    /** The Project Manager's user id, if the project has one. */
    public function projectManagerOf(int $projectId): ?int;

    /**
     * @return list<ProjectInfo> projects whose status is ACTIVE
     */
    public function active(): array;
}
