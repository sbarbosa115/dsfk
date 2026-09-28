<?php

declare(strict_types=1);

namespace App\Notification\Application\Handler;

use App\Notification\Application\Port\NotificationFacts;
use App\Notification\Application\Port\Outbox;
use App\Notification\Application\Port\Recipients;
use App\Planning\Domain\Event\BudgetApproved;
use App\Planning\Domain\Event\BudgetReturned;
use App\Shared\Application\Bus\EventHandler;

/** The PM learns the Admin approved the budget, or returned it with what to change. */
final readonly class NotifyBudgetReviewed implements EventHandler
{
    public function __construct(private Recipients $recipients, private NotificationFacts $facts, private Outbox $outbox)
    {
    }

    public function __invoke(BudgetApproved|BudgetReturned $event): void
    {
        $project = $this->facts->project($event->projectId);
        $approved = $event instanceof BudgetApproved;
        $this->outbox->send(
            [$this->recipients->projectManager($event->projectId)],
            'budget_reviewed',
            ['outcome' => $approved ? 'approved' : 'returned', 'project' => $project['name']],
            [
                'project' => $project,
                'by' => $this->facts->personName($event->byUserId),
                'approved' => $approved,
                'comment' => $event instanceof BudgetReturned ? $event->comment : null,
            ],
        );
    }
}
