<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Project;

use App\Project\Application\Query\ProjectDirectory;
use App\Project\Application\Query\ProjectInfo;
use App\Reporting\Application\Port\ProjectSource;

final readonly class ProjectContextSource implements ProjectSource
{
    public function __construct(private ProjectDirectory $projects)
    {
    }

    public function all(): array
    {
        return array_map(self::row(...), $this->projects->all());
    }

    public function info(int $projectId): array
    {
        return self::row($this->projects->info($projectId));
    }

    /**
     * @return array{id: int, name: string, currency: string, status: string}
     */
    private static function row(ProjectInfo $p): array
    {
        return ['id' => $p->id, 'name' => $p->name, 'currency' => $p->currency, 'status' => $p->status];
    }
}
