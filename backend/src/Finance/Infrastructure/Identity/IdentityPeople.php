<?php

declare(strict_types=1);

namespace App\Finance\Infrastructure\Identity;

use App\Finance\Application\Port\People;
use App\Identity\Application\Query\UserDirectory;

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
