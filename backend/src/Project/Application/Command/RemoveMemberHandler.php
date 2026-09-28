<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Domain\Repository\ProjectRepository;
use App\Shared\Application\Bus\CommandHandler;

final readonly class RemoveMemberHandler implements CommandHandler
{
    public function __construct(private ProjectRepository $projects)
    {
    }

    public function __invoke(RemoveMember $command): void
    {
        $this->projects->get($command->projectId)->removeMember($command->memberId);
    }
}
