<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Model\User;
use App\Shared\Domain\Error\NotFound;

interface UserRepository
{
    /**
     * @throws NotFound user_not_found
     */
    public function get(int $id): User;

    public function findByEmail(string $email): ?User;

    public function add(User $user): void;
}
