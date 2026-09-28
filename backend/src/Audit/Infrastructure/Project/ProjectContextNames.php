<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Project;

use App\Audit\Application\Query\ProjectNames;
use App\Project\Application\Query\ProjectDirectory;
use App\Shared\Domain\Error\NotFound;

final readonly class ProjectContextNames implements ProjectNames
{
    public function __construct(private ProjectDirectory $projects)
    {
    }

    public function names(array $ids): array
    {
        $names = [];
        foreach ($ids as $id) {
            try {
                $names[$id] = $this->projects->info($id)->name;
            } catch (NotFound) {
                // Deleted since.
            }
        }

        return $names;
    }
}
