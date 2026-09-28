<?php

declare(strict_types=1);

namespace App\Shared\Domain\Error;

/** The record does not exist, or the user may not know it exists. */
final class NotFound extends DomainError
{
    public function httpStatus(): int
    {
        return 404;
    }
}
