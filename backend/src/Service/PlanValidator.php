<?php

namespace App\Service;

use App\Entity\Project;

/**
 * Checks that a budget is complete enough to be submitted and approved.
 */
class PlanValidator
{
    public const FULL_WEIGHT = 10000;

    /**
     * @return list<array{code: string, stageId?: int}>
     */
    public function issues(Project $project): array
    {
        $issues = [];
        if ($project->getStages()->isEmpty()) {
            $issues[] = ['code' => 'no_stages'];
        }

        foreach ($project->getStages() as $stage) {
            if ($stage->getBudgetLines()->isEmpty()) {
                $issues[] = ['code' => 'stage_without_lines', 'stageId' => $stage->getId()];
            }
            if (self::FULL_WEIGHT !== $stage->getMilestoneWeightTotal()) {
                $issues[] = ['code' => 'milestone_weights', 'stageId' => $stage->getId()];
            }
        }

        return $issues;
    }
}
