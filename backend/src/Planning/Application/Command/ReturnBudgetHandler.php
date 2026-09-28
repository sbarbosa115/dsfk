<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Event\BudgetReturned;
use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventPublisher;
use Psr\Clock\ClockInterface;

final readonly class ReturnBudgetHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans, private EventPublisher $events, private ClockInterface $clock)
    {
    }

    public function __invoke(ReturnBudget $command): void
    {
        $this->plans->budgetFor($command->projectId)->returnToDraft($command->actorId, $command->comment, $this->clock->now());
        $this->events->publish(new BudgetReturned($command->projectId, $command->actorId, trim($command->comment)));
    }
}
