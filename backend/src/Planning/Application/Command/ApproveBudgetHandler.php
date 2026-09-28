<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Application\Port\ProjectCatalog;
use App\Planning\Domain\Event\BudgetApproved;
use App\Planning\Domain\Repository\PlanRepository;
use App\Planning\Domain\Service\PlanCompleteness;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventPublisher;
use Psr\Clock\ClockInterface;

final readonly class ApproveBudgetHandler implements CommandHandler
{
    public function __construct(
        private PlanRepository $plans,
        private ProjectCatalog $projects,
        private EventPublisher $events,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(ApproveBudget $command): void
    {
        $this->plans->budgetFor($command->projectId)->approve(
            $command->actorId,
            PlanCompleteness::issues($this->plans->stagesOf($command->projectId)),
            $this->clock->now(),
        );
        $this->projects->activate($command->projectId);
        $this->events->publish(new BudgetApproved($command->projectId, $command->actorId));
    }
}
