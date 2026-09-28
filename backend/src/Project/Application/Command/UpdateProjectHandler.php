<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Domain\Repository\ProjectRepository;
use App\Shared\Application\Bus\CommandHandler;

final readonly class UpdateProjectHandler implements CommandHandler
{
    public function __construct(private ProjectRepository $projects)
    {
    }

    public function __invoke(UpdateProject $c): void
    {
        $project = $this->projects->get($c->projectId);

        if ($c->has('currency') && null !== $c->currency) {
            $project->keepCurrency($c->currency);
        }
        if ($c->has('name') && null !== $c->name) {
            $project->rename($c->name);
        }
        if ($c->has('description')) {
            $project->describe($c->description);
        }
        if ($c->has('status') && null !== $c->status) {
            $project->changeStatus($c->status);
        }
        if ($c->has('plannedStart') || $c->has('plannedEnd')) {
            $project->schedule(
                $c->has('plannedStart') ? $c->plannedStart : $project->getPlannedStart(),
                $c->has('plannedEnd') ? $c->plannedEnd : $project->getPlannedEnd(),
            );
        }
    }
}
