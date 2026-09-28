<?php

declare(strict_types=1);

namespace App\Notification\Application\Handler;

use App\Expense\Domain\Event\PettyCashUsed;
use App\Notification\Application\Port\NotificationFacts;
use App\Notification\Application\Port\Outbox;
use App\Notification\Application\Port\Recipients;
use App\Shared\Application\Bus\EventHandler;
use App\Shared\Domain\Money\MinorUnits;

/** Petty cash dropped below its warning level (a share of its last top-up): the Admins and the PM hear it once. */
final readonly class WarnPettyCashLow implements EventHandler
{
    public function __construct(private Recipients $recipients, private NotificationFacts $facts, private Outbox $outbox)
    {
    }

    public function __invoke(PettyCashUsed $event): void
    {
        $cash = $this->facts->pettyCash($event->projectId);
        if ($cash['lastTopUp'] <= 0) {
            return;
        }
        $threshold = intdiv($cash['lastTopUp'] * $this->facts->pettyCashLowPercent(), 100);
        $after = $cash['balance'];
        $before = $after + $event->amount;
        if ($before >= $threshold && $after < $threshold) {
            $project = $this->facts->project($event->projectId);
            $this->outbox->send([...$this->recipients->admins(), $this->recipients->projectManager($event->projectId)], 'petty_cash_low', ['project' => $project['name']], [
                'project' => $project,
                'balance' => MinorUnits::format($after, $project['currency']),
                'lastTopUp' => MinorUnits::format($cash['lastTopUp'], $project['currency']),
            ]);
        }
    }
}
