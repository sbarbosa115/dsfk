<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;

final readonly class DeleteMilestoneHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans)
    {
    }

    public function __invoke(DeleteMilestone $command): void
    {
        $milestone = $this->plans->milestone($command->milestoneId);
        $this->plans->budgetFor($milestone->getStage()->getProjectId())->assertEditable();
        $milestone->getStage()->removeMilestone($milestone);
    }
}
