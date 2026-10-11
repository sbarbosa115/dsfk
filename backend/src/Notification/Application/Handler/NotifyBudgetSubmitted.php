<?php

declare(strict_types=1);

namespace App\Notification\Application\Handler;

use App\Notification\Application\Port\NotificationFacts;
use App\Notification\Application\Port\Outbox;
use App\Notification\Application\Port\Recipients;
use App\Planning\Domain\Event\BudgetSubmitted;
use App\Shared\Application\Bus\EventHandler;

/** The Admins review a budget, or an addition to it, sent for approval. */
final readonly class NotifyBudgetSubmitted implements EventHandler
{
    public function __construct(private Recipients $recipients, private NotificationFacts $facts, private Outbox $outbox)
    {
    }

    public function __invoke(BudgetSubmitted $event): void
    {
        $project = $this->facts->project($event->projectId);
        $this->outbox->send($this->recipients->admins(), 'budget_submitted', ['project' => $project['name'], 'amendment' => $event->amendment ?? 'none'], [
            'project' => $project,
            'amendment' => $event->amendment ?? 'none',
            'by' => $this->facts->personName($event->byUserId),
        ]);
    }
}
