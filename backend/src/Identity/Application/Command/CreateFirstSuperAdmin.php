<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

/** `app:create-admin`: creates a super admin without a signed-in actor (first login of an install). */
final readonly class CreateFirstSuperAdmin
{
    public function __construct(public string $email, public string $fullName, public string $password)
    {
    }
}
