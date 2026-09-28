<?php

declare(strict_types=1);

namespace App\Planning\Infrastructure\Identity;

use App\Identity\Application\Query\UserDirectory;
use App\Planning\Application\Port\People;

final readonly class IdentityPeople implements People
{
    public function __construct(private UserDirectory $users)
    {
    }

    public function names(array $userIds): array
    {
        return array_map(static fn ($user): string => $user->fullName, $this->users->views($userIds));
    }
}
