<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

final readonly class DeleteMilestone
{
    public function __construct(public int $milestoneId)
    {
    }
}
