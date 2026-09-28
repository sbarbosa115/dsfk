<?php

declare(strict_types=1);

namespace App\Planning\Domain\Service;

use App\Planning\Domain\Model\Stage;

/**
 * What stops a budget from being submitted or approved: there are no stages, a stage has no budget line, or a
 * stage's milestone weights do not add up to exactly 100 %.
 */
final class PlanCompleteness
{
    /**
     * @param list<Stage> $stages
     *
     * @return list<array{code: string, stageId?: int}>
     */
    public static function issues(array $stages): array
    {
        if ([] === $stages) {
            return [['code' => 'no_stages']];
        }
        $issues = [];
        foreach ($stages as $stage) {
            if ([] === $stage->getLines()) {
                $issues[] = ['code' => 'stage_without_lines', 'stageId' => (int) $stage->getId()];
            }
            if (Stage::FULL_WEIGHT !== $stage->milestoneWeightTotal()) {
                $issues[] = ['code' => 'milestone_weights', 'stageId' => (int) $stage->getId()];
            }
        }

        return $issues;
    }
}
