<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

final readonly class UserSummary
{
    public function __construct(
        public int $id,
        public string $email,
        public string $fullName,
        public bool $admin,
        public bool $active,
    ) {
    }
}
