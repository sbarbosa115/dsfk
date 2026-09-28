<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

/** Undoing a completion changes the reported progress, so only the Admin does it. */
final readonly class ReopenMilestone
{
    public function __construct(public int $milestoneId)
    {
    }
}
