<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Project\Domain\Model\Project;

final readonly class ProjectPage
{
    /**
     * @param list<Project> $items
     */
    public function __construct(public array $items, public int $total)
    {
    }
}
