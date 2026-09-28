<?php

declare(strict_types=1);

namespace App\Project\Application\Port;

use App\Project\Application\Query\UserSummary;

/** People, from the Identity context: who can be a member, and the names shown next to members. */
interface UserDirectory
{
    public function find(int $userId): ?UserSummary;

    /**
     * @param list<int> $userIds
     *
     * @return array<int, UserSummary> by id; unknown ids are left out
     */
    public function findMany(array $userIds): array;
}
