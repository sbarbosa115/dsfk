<?php

declare(strict_types=1);

namespace App\Shared\Domain\Error;

/** The user may not do this. */
final class NotAllowed extends DomainError
{
    public function httpStatus(): int
    {
        return 403;
    }
}
