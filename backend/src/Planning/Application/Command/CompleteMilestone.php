<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

/** After approval: the milestone was met on that date (not in the future), with optional notes. */
final readonly class CompleteMilestone
{
    public function __construct(
        public int $milestoneId,
        public int $actorId,
        public \DateTimeImmutable $completedAt,
        public ?string $notes = null,
    ) {
    }
}
