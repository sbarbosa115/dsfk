<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Query\Membership;

/**
 * The projects people belong to, from the Project context: shown with the signed-in user and in the users list.
 */
interface MembershipDirectory
{
    /**
     * @return list<Membership>
     */
    public function ofUser(int $userId): array;

    /**
     * @return array<int, list<Membership>> by user id
     */
    public function ofAllUsers(): array;
}
