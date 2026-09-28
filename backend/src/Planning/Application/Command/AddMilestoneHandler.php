<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;

final readonly class AddMilestoneHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans)
    {
    }

    public function __invoke(AddMilestone $c): void
    {
        $stage = $this->plans->stage($c->stageId);
        $this->plans->budgetFor($stage->getProjectId())->assertEditable();
        $stage->addMilestone($c->name, $c->weight, $c->plannedDate);
    }
}
