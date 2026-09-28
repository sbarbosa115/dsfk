<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

/** The Admin approves: the budget is locked and a draft project becomes active. */
final readonly class ApproveBudget
{
    public function __construct(public int $projectId, public int $actorId)
    {
    }
}
