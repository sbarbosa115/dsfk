<?php

declare(strict_types=1);

namespace App\Project\Domain\Model;

enum ProjectStatus: string
{
    case Draft = 'DRAFT';
    case Active = 'ACTIVE';
    case Completed = 'COMPLETED';
    case Archived = 'ARCHIVED';
}
