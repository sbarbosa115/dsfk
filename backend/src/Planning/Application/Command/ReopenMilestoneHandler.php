<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\Error\Conflict;

final readonly class ReopenMilestoneHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans)
    {
    }

    public function __invoke(ReopenMilestone $command): void
    {
        $milestone = $this->plans->milestone($command->milestoneId);
        if ($milestone->getStage()->isCompleted()) {
            // A completed stage has every milestone met; reopening one would contradict it.
            throw new Conflict('stage_completed');
        }
        $milestone->reopen();
    }
}
