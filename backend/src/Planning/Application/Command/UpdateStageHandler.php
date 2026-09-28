<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;

final readonly class UpdateStageHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans)
    {
    }

    public function __invoke(UpdateStage $c): void
    {
        $stage = $this->plans->stage($c->stageId);
        if ($c->has('name') && null !== $c->name) {
            $stage->rename($c->name);
        }
        if ($c->has('plannedStart') || $c->has('plannedEnd')) {
            $this->plans->budgetFor($stage->getProjectId())->assertEditable();
            $stage->schedule(
                $c->has('plannedStart') ? $c->plannedStart : $stage->getPlannedStart(),
                $c->has('plannedEnd') ? $c->plannedEnd : $stage->getPlannedEnd(),
            );
        }
    }
}
