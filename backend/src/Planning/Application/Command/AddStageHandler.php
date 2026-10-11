<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Model\Stage;
use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;
use Psr\Clock\ClockInterface;

final readonly class AddStageHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans, private ClockInterface $clock)
    {
    }

    public function __invoke(AddStage $command): void
    {
        // Once the budget is approved a new stage is a proposal: it joins the open amendment (Adición).
        $amendment = $this->plans->budgetFor($command->projectId)->amendmentToEdit($this->clock->now());
        $stages = $this->plans->stagesOf($command->projectId);
        $position = [] === $stages ? 0 : max(array_map(static fn (Stage $s): int => $s->getPosition(), $stages)) + 1;
        $stage = new Stage($command->projectId, $command->name, $position);
        $stage->schedule($command->plannedStart, $command->plannedEnd);
        if (null !== $amendment) {
            $stage->joinAmendment($amendment);
        }
        $this->plans->addStage($stage);
    }
}
