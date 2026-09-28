<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Application\Port\DefaultCurrency;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\NewId;
use Psr\Clock\ClockInterface;

final readonly class CreateProjectHandler implements CommandHandler
{
    public function __construct(private ProjectRepository $projects, private DefaultCurrency $defaultCurrency, private ClockInterface $clock)
    {
    }

    public function __invoke(CreateProject $command): NewId
    {
        $project = new Project($command->name, $command->currency ?? $this->defaultCurrency->code(), $this->clock->now());
        $project->describe($command->description);
        $project->schedule($command->plannedStart, $command->plannedEnd);
        if (null !== $command->status) {
            $project->changeStatus($command->status);
        }
        $this->projects->add($project);

        return NewId::of($project);
    }
}
