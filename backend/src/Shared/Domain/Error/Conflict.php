<?php

declare(strict_types=1);

namespace App\Shared\Domain\Error;

/** The record's state does not allow this now. */
final class Conflict extends DomainError
{
    public function httpStatus(): int
    {
        return 409;
    }
}
