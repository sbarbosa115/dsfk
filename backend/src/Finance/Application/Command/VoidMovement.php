<?php

declare(strict_types=1);

namespace App\Finance\Application\Command;

/** The Admin voids a deposit or draw recorded by mistake; it stays listed, out of the balances. */
final readonly class VoidMovement
{
    public function __construct(public int $movementId, public int $actorId, public string $reason)
    {
    }
}
