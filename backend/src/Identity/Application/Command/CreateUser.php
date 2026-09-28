<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

final readonly class CreateUser
{
    public function __construct(
        public int $actorId,
        public string $email,
        public string $fullName,
        public string $password,
        public bool $admin = false,
        public bool $superAdmin = false,
    ) {
    }
}
