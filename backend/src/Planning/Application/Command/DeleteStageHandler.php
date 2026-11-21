<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Application\Port\StageUsage;
use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\Error\Conflict;

/**
 * A stage goes with its lines and milestones: freely while it is being written, and once approved only if it never
 * started, no milestone was met and no money (fund assignments, expenses) touched it.
 */
final readonly class DeleteStageHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans, private StageUsage $usage)
    {
    }

    public function __invoke(DeleteStage $command): void
    {
        $stage = $this->plans->stage($command->stageId);
        $budget = $this->plans->budgetFor($stage->getProjectId());
        $budget->assertStageDeletable($stage);
        if (!$budget->isStageEditable($stage) && \in_array($command->stageId, $this->usage->usedStages($stage->getProjectId()), true)) {
            throw new Conflict('stage_in_use');
        }
        $this->plans->removeStage($stage);
        $amendment = $stage->getAmendment();
        // An amendment still being written goes once emptied; an approved one stays, as history.
        if (null !== $amendment && $amendment->isEditable() && [] === array_filter($this->plans->stagesOf($stage->getProjectId()), static fn ($s): bool => $s !== $stage && $s->getAmendment() === $amendment)) {
            $budget->dropAmendment($amendment);
        }
    }
}
