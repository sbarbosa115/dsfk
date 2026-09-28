<?php

declare(strict_types=1);

namespace App\Shared\Domain\Error;

/** A value breaks a rule. */
final class InvalidValue extends DomainError
{
    public function httpStatus(): int
    {
        return 422;
    }
}
