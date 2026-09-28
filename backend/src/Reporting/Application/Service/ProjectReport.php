<?php

declare(strict_types=1);

namespace App\Reporting\Application\Service;

use App\Reporting\Domain\Model\ProjectFigures;

/** A project's dashboard, minor units. */
final readonly class ProjectReport
{
    /**
     * @param list<array{month: string, deposited: int, spent: int}> $monthly
     * @param list<DashboardAlert>                                   $alerts
     */
    public function __construct(
        public int $projectId,
        public string $name,
        public string $status,
        public string $currency,
        public bool $approved,
        public int $contingency,
        public int $deposited,
        public int $available,
        public int $pettyCash,
        /** Basis points. */
        public int $progress,
        public ProjectFigures $figures,
        public array $monthly,
        public array $alerts,
    ) {
    }
}
