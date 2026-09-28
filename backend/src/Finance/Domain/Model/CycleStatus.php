<?php

declare(strict_types=1);

namespace App\Finance\Domain\Model;

enum CycleStatus: string
{
    case Open = 'OPEN';
    case Closed = 'CLOSED';
    case SignedOff = 'SIGNED_OFF';
}
