<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

/** A user as other contexts see them. */
final readonly class UserView
{
    public function __construct(
        public int $id,
        public string $email,
        public string $fullName,
        public bool $admin,
        public bool $superAdmin,
        public bool $active,
    ) {
    }
}
