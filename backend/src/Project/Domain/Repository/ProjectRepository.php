<?php

declare(strict_types=1);

namespace App\Project\Domain\Repository;

use App\Project\Domain\Model\Project;
use App\Shared\Domain\Error\NotFound;

interface ProjectRepository
{
    /**
     * @throws NotFound project_not_found
     */
    public function get(int $id): Project;

    public function add(Project $project): void;
}
