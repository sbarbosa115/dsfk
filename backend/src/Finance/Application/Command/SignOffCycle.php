<?php

declare(strict_types=1);

namespace App\Finance\Application\Command;

/** An Admin reviewed a closed caja menor cycle. */
final readonly class SignOffCycle
{
    public function __construct(public int $cycleId, public int $actorId)
    {
    }
}
