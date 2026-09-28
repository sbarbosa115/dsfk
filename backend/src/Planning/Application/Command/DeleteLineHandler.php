<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;

final readonly class DeleteLineHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans)
    {
    }

    public function __invoke(DeleteLine $command): void
    {
        $line = $this->plans->line($command->lineId);
        $this->plans->budgetFor($line->getStage()->getProjectId())->assertEditable();
        $line->getStage()->removeLine($line);
    }
}
