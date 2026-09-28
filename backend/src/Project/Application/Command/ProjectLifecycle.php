<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Domain\Repository\ProjectRepository;

/**
 * What other contexts change on a project inside their own command's transaction: approving the budget
 * (Planning) activates the project.
 */
final readonly class ProjectLifecycle
{
    public function __construct(private ProjectRepository $projects)
    {
    }

    public function activate(int $projectId): void
    {
        $this->projects->get($projectId)->activate();
    }
}
