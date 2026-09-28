<?php

declare(strict_types=1);

namespace App\Notification\Application\Handler;

use App\Finance\Domain\Event\CycleClosed;
use App\Notification\Application\Port\NotificationFacts;
use App\Notification\Application\Port\Outbox;
use App\Notification\Application\Port\Recipients;
use App\Shared\Application\Bus\EventHandler;
use App\Shared\Domain\Money\MinorUnits;

/** The Admins review and sign off a closed caja menor cycle. */
final readonly class NotifyCycleClosed implements EventHandler
{
    public function __construct(private Recipients $recipients, private NotificationFacts $facts, private Outbox $outbox)
    {
    }

    public function __invoke(CycleClosed $event): void
    {
        $cycle = $this->facts->cycle($event->cycleId);
        if (null === $cycle) {
            return;
        }
        $project = $this->facts->project($event->projectId);
        $this->outbox->send($this->recipients->admins(), "Caja menor ciclo {$cycle['number']} cerrado: {$project['name']}", 'cycle_closed', [
            'project' => $project,
            'cycle' => $cycle,
            'closedBy' => null === $cycle['closedById'] ? '' : $this->facts->personName($cycle['closedById']),
            'balance' => MinorUnits::format($cycle['closingBalance'], $project['currency']),
        ]);
    }
}
