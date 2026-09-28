<?php

declare(strict_types=1);

namespace App\Finance\Application\Command;

use App\Finance\Domain\Repository\LedgerRepository;
use App\Shared\Application\Bus\CommandHandler;
use Psr\Clock\ClockInterface;

final readonly class VoidMovementHandler implements CommandHandler
{
    public function __construct(private LedgerRepository $ledger, private ClockInterface $clock)
    {
    }

    public function __invoke(VoidMovement $c): void
    {
        $movement = $this->ledger->movement($c->movementId);
        $movement->void($this->ledger->balances($movement->getProjectId()), $c->actorId, $c->reason, $this->clock->now());
    }
}
