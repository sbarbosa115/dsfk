<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Identity;

use App\Identity\Application\Query\UserDirectory;
use App\Identity\Application\Query\UserView;
use App\Notification\Application\Port\Recipient;
use App\Notification\Application\Port\Recipients;
use App\Project\Application\Query\ProjectDirectory;

final readonly class DirectoryRecipients implements Recipients
{
    public function __construct(private UserDirectory $users, private ProjectDirectory $projects)
    {
    }

    public function admins(): array
    {
        return array_map(self::recipient(...), $this->users->admins());
    }

    public function projectManager(int $projectId): ?Recipient
    {
        $pm = $this->projects->projectManagerOf($projectId);

        return null === $pm ? null : $this->person($pm);
    }

    public function person(int $userId): ?Recipient
    {
        $user = $this->users->view($userId);

        return null !== $user && $user->active ? self::recipient($user) : null;
    }

    private static function recipient(UserView $user): Recipient
    {
        return new Recipient($user->email, $user->fullName);
    }
}
