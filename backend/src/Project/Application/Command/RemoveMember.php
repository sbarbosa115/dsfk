<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

final readonly class RemoveMember
{
    public function __construct(public int $projectId, public int $memberId)
    {
    }
}
