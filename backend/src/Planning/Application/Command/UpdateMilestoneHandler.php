<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;

final readonly class UpdateMilestoneHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans)
    {
    }

    public function __invoke(UpdateMilestone $c): void
    {
        $milestone = $this->plans->milestone($c->milestoneId);
        $stage = $milestone->getStage();
        $this->plans->budgetFor($stage->getProjectId())->assertEditable();
        if ($c->has('name') && null !== $c->name) {
            $milestone->rename($c->name);
        }
        if ($c->has('weight') && null !== $c->weight) {
            $stage->reweigh($milestone, $c->weight);
        }
        if ($c->has('plannedDate')) {
            $milestone->planFor($c->plannedDate);
        }
    }
}
