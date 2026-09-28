<?php

declare(strict_types=1);

namespace App\Shared\Domain\Error;

/** The request makes no sense in the current state. */
final class Rejected extends DomainError
{
    public function httpStatus(): int
    {
        return 400;
    }
}
