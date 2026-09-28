<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Project\Domain\Model\Project;

interface ProjectQueries
{
    /**
     * Newest first. A null member id means every project (admins); otherwise only the projects that person
     * belongs to. `search` matches the name, with % and _ taken literally.
     */
    public function page(?int $memberId, ?string $search, int $page, int $perPage): ProjectPage;

    public function byId(int $id): ?Project;
}
