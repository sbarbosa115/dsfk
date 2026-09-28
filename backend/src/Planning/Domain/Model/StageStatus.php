<?php

declare(strict_types=1);

namespace App\Planning\Domain\Model;

enum StageStatus: string
{
    case Pending = 'PENDING';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
}
