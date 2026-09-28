<?php

declare(strict_types=1);

namespace App\Finance\Domain\Event;

/** The PM (or an Admin) closed a petty cash cycle: an Admin reviews and signs it off. */
final readonly class CycleClosed
{
    public function __construct(public int $cycleId, public int $projectId)
    {
    }
}
