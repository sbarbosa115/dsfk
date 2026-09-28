<?php

declare(strict_types=1);

namespace App\Finance\Application\Command;

use App\Finance\Domain\Event\CycleClosed;
use App\Finance\Domain\Model\LedgerAccount;
use App\Finance\Domain\Repository\LedgerRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventPublisher;
use App\Shared\Application\Bus\NewId;
use Psr\Clock\ClockInterface;

final readonly class CloseCycleHandler implements CommandHandler
{
    public function __construct(private LedgerRepository $ledger, private EventPublisher $events, private ClockInterface $clock)
    {
    }

    public function __invoke(CloseCycle $c): NewId
    {
        $this->ledger->lock($c->projectId);
        $cycle = $this->ledger->openCycle($c->projectId, $this->clock->now());
        $cycle->close($c->actorId, $this->ledger->balances($c->projectId)->of(LedgerAccount::PettyCash), $c->note, $this->clock->now());
        $this->events->publish(new CycleClosed($this->ledger->saveCycle($cycle), $c->projectId));

        return NewId::of($cycle);
    }
}
