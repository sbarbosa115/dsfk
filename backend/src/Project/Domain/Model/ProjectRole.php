<?php

declare(strict_types=1);

namespace App\Project\Domain\Model;

/** A person's role in one project. Admin is global and never a member. */
enum ProjectRole: string
{
    case ProjectManager = 'PROJECT_MANAGER';
    case TeamLead = 'TEAM_LEAD';
}
