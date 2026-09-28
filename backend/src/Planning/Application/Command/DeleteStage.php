<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

final readonly class DeleteStage
{
    public function __construct(public int $stageId)
    {
    }
}
