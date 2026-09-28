<?php

declare(strict_types=1);

namespace App\Shared\Domain\Error;

/** Too many attempts in a short time. */
final class TooManyAttempts extends DomainError
{
    public function httpStatus(): int
    {
        return 429;
    }
}
