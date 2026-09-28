<?php

declare(strict_types=1);

namespace App\Notification\Application\Service;

use App\Notification\Application\Port\NotificationFacts;
use App\Notification\Application\Port\Outbox;
use App\Notification\Application\Port\Recipients;
use Psr\Clock\ClockInterface;

/**
 * Once a day, per active project with something pending (late milestones, expenses to approve or pay back,
 * cycles to sign off), a summary for its PM and the Admins.
 */
final readonly class DailyDigest
{
    public function __construct(
        private Recipients $recipients,
        private NotificationFacts $facts,
        private Outbox $outbox,
        private ClockInterface $clock,
    ) {
    }

    /** @return int how many projects had something to say */
    public function send(): int
    {
        $today = $this->clock->now();
        $sent = 0;
        foreach ($this->facts->activeProjects() as $projectId) {
            $digest = $this->facts->digest($projectId, $today);
            if ([] === $digest['overdue'] && 0 === $digest['pending'] + $digest['toReimburse'] + $digest['unsigned']) {
                continue;
            }
            $project = $this->facts->project($projectId);
            $this->outbox->send([...$this->recipients->admins(), $this->recipients->projectManager($projectId)], 'daily_digest', ['project' => $project['name']], ['project' => $project] + $digest);
            ++$sent;
        }

        return $sent;
    }
}
