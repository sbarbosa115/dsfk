<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

final readonly class ReorderStages
{
    /**
     * @param list<int> $stageIds every stage of the project, in the new order
     */
    public function __construct(public int $projectId, public array $stageIds)
    {
    }
}
