<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;

final readonly class DeleteStageHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans)
    {
    }

    public function __invoke(DeleteStage $command): void
    {
        $stage = $this->plans->stage($command->stageId);
        $budget = $this->plans->budgetFor($stage->getProjectId());
        $budget->assertStageEditable($stage);
        $this->plans->removeStage($stage);
        $amendment = $stage->getAmendment();
        if (null !== $amendment && [] === array_filter($this->plans->stagesOf($stage->getProjectId()), static fn ($s): bool => $s !== $stage && $s->getAmendment() === $amendment)) {
            $budget->dropAmendment($amendment);
        }
    }
}
