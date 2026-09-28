<?php

declare(strict_types=1);

namespace App\Expense\Application\Port;

/** Names of people, from the Identity context. */
interface People
{
    /**
     * @param list<int> $userIds
     *
     * @return array<int, string> full name by user id
     */
    public function names(array $userIds): array;
}
