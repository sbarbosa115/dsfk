<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

/** An admin edits a user. A null field is left as it is. */
final readonly class UpdateUser
{
    public function __construct(
        public int $actorId,
        public int $userId,
        public ?string $email = null,
        public ?string $fullName = null,
        public ?string $password = null,
        public ?bool $admin = null,
        public ?bool $superAdmin = null,
        public ?bool $active = null,
    ) {
    }
}
