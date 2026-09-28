<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\Error\InvalidValue;

final readonly class ReorderStagesHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans)
    {
    }

    public function __invoke(ReorderStages $command): void
    {
        $this->plans->budgetFor($command->projectId)->assertEditable();
        $stages = [];
        foreach ($this->plans->stagesOf($command->projectId) as $stage) {
            $stages[(int) $stage->getId()] = $stage;
        }
        $given = $command->stageIds;
        $existing = array_keys($stages);
        sort($given);
        sort($existing);
        if ($given !== $existing) {
            throw InvalidValue::field('ids', 'La lista debe contener todas las etapas del proyecto, una vez cada una.');
        }
        foreach ($command->stageIds as $position => $id) {
            $stages[$id]->moveTo($position);
        }
    }
}
