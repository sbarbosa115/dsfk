<?php

declare(strict_types=1);

namespace App\Reporting\Application\Port;

use App\Reporting\Domain\Model\StagePlan;

/** The Planning context. */
interface PlanSource
{
    public function isApproved(int $projectId): bool;

    /** Minor units. */
    public function contingency(int $projectId): int;

    /** Physical progress, basis points. */
    public function progress(int $projectId): int;

    /**
     * @return list<StagePlan> in their order
     */
    public function stages(int $projectId): array;
}
