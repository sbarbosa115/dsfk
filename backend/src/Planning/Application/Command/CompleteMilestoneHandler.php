<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;
use Psr\Clock\ClockInterface;

final readonly class CompleteMilestoneHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans, private ClockInterface $clock)
    {
    }

    public function __invoke(CompleteMilestone $command): void
    {
        $milestone = $this->plans->milestone($command->milestoneId);
        $this->plans->budgetFor($milestone->getStage()->getProjectId())->assertApproved();
        $milestone->complete($command->completedAt, $command->actorId, $command->notes, $this->clock->now());
    }
}
