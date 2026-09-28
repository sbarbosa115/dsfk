<?php

declare(strict_types=1);

namespace App\Planning\Infrastructure\Project;

use App\Planning\Application\Port\ProjectCatalog;
use App\Project\Application\Command\ProjectLifecycle;
use App\Project\Application\Query\ProjectDirectory;

final readonly class ProjectContextCatalog implements ProjectCatalog
{
    public function __construct(private ProjectDirectory $projects, private ProjectLifecycle $lifecycle)
    {
    }

    public function describe(int $projectId): array
    {
        $info = $this->projects->info($projectId);

        return ['name' => $info->name, 'currency' => $info->currency, 'status' => $info->status];
    }

    public function currency(int $projectId): string
    {
        return $this->projects->info($projectId)->currency;
    }

    public function activate(int $projectId): void
    {
        $this->lifecycle->activate($projectId);
    }
}
