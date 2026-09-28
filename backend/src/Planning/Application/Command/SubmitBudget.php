<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

final readonly class SubmitBudget
{
    public function __construct(public int $projectId, public int $actorId)
    {
    }
}
