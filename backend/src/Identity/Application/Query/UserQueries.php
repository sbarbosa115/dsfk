<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

use App\Identity\Domain\Model\User;
use App\Shared\Application\Query\Page;

interface UserQueries
{
    /**
     * Sorted by name. `status`: 'active' (default), 'inactive' or 'all'. `search` matches name and email.
     *
     * @return Page<User>
     */
    public function page(?string $search, string $status, int $page, int $perPage): Page;

    public function byId(int $id): ?User;
}
