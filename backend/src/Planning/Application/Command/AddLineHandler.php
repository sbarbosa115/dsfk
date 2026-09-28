<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Application\Port\ProjectCatalog;
use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;

final readonly class AddLineHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans, private ProjectCatalog $projects)
    {
    }

    public function __invoke(AddLine $c): void
    {
        $stage = $this->plans->stage($c->stageId);
        $this->plans->budgetFor($stage->getProjectId())->assertEditable();
        $stage->addLine(
            LineCategory::of($this->plans, $c->categoryId),
            $c->description,
            $c->unit,
            $c->quantity,
            LineCategory::price($c->unitPrice, $this->projects->currency($stage->getProjectId())),
        );
    }
}
