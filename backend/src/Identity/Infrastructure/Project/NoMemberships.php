<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Project;

use App\Identity\Application\Port\MembershipDirectory;

/** Until the Project context exists, nobody belongs to a project. */
final class NoMemberships implements MembershipDirectory
{
    public function ofUser(int $userId): array
    {
        return [];
    }

    public function ofAllUsers(): array
    {
        return [];
    }
}
