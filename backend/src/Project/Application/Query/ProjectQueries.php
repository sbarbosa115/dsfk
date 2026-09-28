<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Project\Domain\Model\Project;
use App\Project\Domain\Model\ProjectStatus;
use App\Shared\Application\Query\Page;

interface ProjectQueries
{
    /**
     * Newest first. A null member id means every project (admins); otherwise only the projects that person
     * belongs to. `search` matches the name, with % and _ taken literally; `status` narrows to one status.
     *
     * @return Page<Project>
     */
    public function page(?int $memberId, ?string $search, ?ProjectStatus $status, int $page, int $perPage): Page;

    public function byId(int $id): ?Project;
}
