<?php

declare(strict_types=1);

namespace App\Audit\Application\Query;

/** The Project context: names to show next to each change. */
interface ProjectNames
{
    /**
     * @param list<int> $ids
     *
     * @return array<int, string> name by project id; deleted projects are left out
     */
    public function names(array $ids): array;
}
