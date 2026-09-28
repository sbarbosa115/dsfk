<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Identity;

use App\Identity\Application\Query\UserDirectory as IdentityUsers;
use App\Identity\Application\Query\UserView;
use App\Project\Application\Port\UserDirectory;
use App\Project\Application\Query\UserSummary;

final readonly class IdentityUserDirectory implements UserDirectory
{
    public function __construct(private IdentityUsers $users)
    {
    }

    public function find(int $userId): ?UserSummary
    {
        $user = $this->users->view($userId);

        return null === $user ? null : self::summary($user);
    }

    public function findMany(array $userIds): array
    {
        return array_map(self::summary(...), $this->users->views($userIds));
    }

    private static function summary(UserView $user): UserSummary
    {
        return new UserSummary($user->id, $user->email, $user->fullName, $user->admin, $user->active);
    }
}
