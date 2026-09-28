<?php

declare(strict_types=1);

namespace App\Audit\Application\Query;

use App\Audit\Domain\Model\AuditRecord;
use App\Shared\Application\Query\Page;

interface AuditQueries
{
    /**
     * Newest first. `$search` looks in the person's name.
     *
     * @return Page<AuditRecord>
     */
    public function page(?int $projectId, ?string $entityType, ?string $search, int $page, int $perPage): Page;

    /**
     * @return list<string> the kinds of record in the trail, by name
     */
    public function entityTypes(): array;
}
