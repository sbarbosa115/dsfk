<?php

declare(strict_types=1);

namespace App\Finance\Application\Port;

/** Names of people, from the Identity context: who recorded or voided a movement. */
interface People
{
    /**
     * @param list<int> $userIds
     *
     * @return array<int, string> full name by user id
     */
    public function names(array $userIds): array;
}
