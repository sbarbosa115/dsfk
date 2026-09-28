<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

use App\Identity\Domain\Model\User;

interface UserQueries
{
    /**
     * @return list<User> sorted by name
     */
    public function all(): array;

    public function byId(int $id): ?User;
}
