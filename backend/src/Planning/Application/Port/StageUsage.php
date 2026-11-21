<?php

declare(strict_types=1);

namespace App\Planning\Application\Port;

/** Whether money outside the plan (fund assignments, expenses) touches a stage, so an approved one cannot be deleted. */
interface StageUsage
{
    /**
     * The project's stages that money has touched.
     *
     * @return list<int>
     */
    public function usedStages(int $projectId): array;
}
