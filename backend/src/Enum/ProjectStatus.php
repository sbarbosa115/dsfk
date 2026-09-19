<?php

namespace App\Enum;

enum ProjectStatus: string
{
    case Draft = 'DRAFT';
    case Active = 'ACTIVE';
    case Completed = 'COMPLETED';
    case Archived = 'ARCHIVED';
}
