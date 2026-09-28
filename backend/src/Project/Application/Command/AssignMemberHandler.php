<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Application\Port\UserDirectory;
use App\Project\Domain\Repository\ProjectRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\Error\InvalidValue;

final readonly class AssignMemberHandler implements CommandHandler
{
    public function __construct(private ProjectRepository $projects, private UserDirectory $users)
    {
    }

    public function __invoke(AssignMember $command): void
    {
        $project = $this->projects->get($command->projectId);
        $user = $this->users->find($command->userId) ?? throw new InvalidValue('user_not_found');
        if ($user->admin) {
            // Admins already see and do everything in every project.
            throw new InvalidValue('admin_is_global');
        }
        if (!$user->active) {
            throw new InvalidValue('user_inactive');
        }

        $project->assign($command->userId, $command->role);
    }
}
