<?php

declare(strict_types=1);

namespace App\Expense\Domain\Event;

/** Money left the caja menor (for the low balance warning). */
final readonly class PettyCashUsed
{
    public function __construct(public int $projectId)
    {
    }
}
