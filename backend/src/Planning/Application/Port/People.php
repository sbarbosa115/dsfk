<?php

declare(strict_types=1);

namespace App\Planning\Application\Port;

/** Names of people, from the Identity context: who moved the budget, who met a milestone. */
interface People
{
    /**
     * @param list<int> $userIds
     *
     * @return array<int, string> full name by user id
     */
    public function names(array $userIds): array;
}
