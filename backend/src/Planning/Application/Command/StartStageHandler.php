<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;
use Psr\Clock\ClockInterface;

final readonly class StartStageHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans, private ClockInterface $clock)
    {
    }

    public function __invoke(StartStage $command): void
    {
        $stage = $this->plans->stage($command->stageId);
        $this->plans->budgetFor($stage->getProjectId())->assertApproved();
        $stage->start($command->actualStart, $this->clock->now());
    }
}
