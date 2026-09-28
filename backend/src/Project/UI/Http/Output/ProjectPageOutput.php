<?php

declare(strict_types=1);

namespace App\Project\UI\Http\Output;

use App\Shared\UI\Http\PageOutput;

/**
 * @extends PageOutput<ProjectOutput>
 */
final readonly class ProjectPageOutput extends PageOutput
{
    /**
     * @param list<ProjectOutput> $items
     */
    public function __construct(array $items, int $total, int $page, int $perPage)
    {
        parent::__construct($items, $total, $page, $perPage);
    }
}
