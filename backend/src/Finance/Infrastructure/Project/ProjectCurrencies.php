<?php

declare(strict_types=1);

namespace App\Finance\Infrastructure\Project;

use App\Finance\Application\Port\Currencies;
use App\Project\Application\Query\ProjectDirectory;

final readonly class ProjectCurrencies implements Currencies
{
    public function __construct(private ProjectDirectory $projects)
    {
    }

    public function of(int $projectId): string
    {
        return $this->projects->info($projectId)->currency;
    }
}
