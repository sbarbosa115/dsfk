<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

/** Categories can be added even after approval, so unplanned costs have somewhere to go. */
final readonly class AddCategory
{
    public function __construct(public int $projectId, public string $name)
    {
    }
}
