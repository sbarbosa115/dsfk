<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Model\Stage;
use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;

final readonly class AddStageHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans)
    {
    }

    public function __invoke(AddStage $command): void
    {
        $this->plans->budgetFor($command->projectId)->assertEditable();
        $stages = $this->plans->stagesOf($command->projectId);
        $position = [] === $stages ? 0 : max(array_map(static fn (Stage $s): int => $s->getPosition(), $stages)) + 1;
        $stage = new Stage($command->projectId, $command->name, $position);
        $stage->schedule($command->plannedStart, $command->plannedEnd);
        $this->plans->addStage($stage);
    }
}
