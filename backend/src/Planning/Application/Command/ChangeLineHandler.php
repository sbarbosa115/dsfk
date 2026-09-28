<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Application\Port\ProjectCatalog;
use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;

final readonly class ChangeLineHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans, private ProjectCatalog $projects)
    {
    }

    public function __invoke(ChangeLine $c): void
    {
        $line = $this->plans->line($c->lineId);
        $stage = $line->getStage();
        $this->plans->budgetFor($stage->getProjectId())->assertEditable();
        $stage->changeLine(
            $line,
            LineCategory::of($this->plans, $c->categoryId),
            $c->description,
            $c->unit,
            $c->quantity,
            LineCategory::price($c->unitPrice, $this->projects->currency($stage->getProjectId())),
        );
    }
}
