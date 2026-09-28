<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Event\BudgetSubmitted;
use App\Planning\Domain\Repository\PlanRepository;
use App\Planning\Domain\Service\PlanCompleteness;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventPublisher;
use Psr\Clock\ClockInterface;

final readonly class SubmitBudgetHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans, private EventPublisher $events, private ClockInterface $clock)
    {
    }

    public function __invoke(SubmitBudget $command): void
    {
        $this->plans->budgetFor($command->projectId)->submit(
            $command->actorId,
            PlanCompleteness::issues($this->plans->stagesOf($command->projectId)),
            $this->clock->now(),
        );
        $this->events->publish(new BudgetSubmitted($command->projectId, $command->actorId));
    }
}
