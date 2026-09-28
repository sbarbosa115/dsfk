<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

/** What other contexts need to know about a project. */
final readonly class ProjectInfo
{
    public function __construct(
        public int $id,
        public string $name,
        public string $currency,
        /** DRAFT, ACTIVE, COMPLETED or ARCHIVED */
        public string $status,
    ) {
    }
}
