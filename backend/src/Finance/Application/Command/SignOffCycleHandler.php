<?php

declare(strict_types=1);

namespace App\Finance\Application\Command;

use App\Finance\Domain\Repository\LedgerRepository;
use App\Shared\Application\Bus\CommandHandler;
use Psr\Clock\ClockInterface;

final readonly class SignOffCycleHandler implements CommandHandler
{
    public function __construct(private LedgerRepository $ledger, private ClockInterface $clock)
    {
    }

    public function __invoke(SignOffCycle $c): void
    {
        $this->ledger->cycle($c->cycleId)->signOff($c->actorId, $this->clock->now());
    }
}
