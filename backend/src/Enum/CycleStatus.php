<?php

namespace App\Enum;

enum CycleStatus: string
{
    case Open = 'OPEN';
    case Closed = 'CLOSED';
    case SignedOff = 'SIGNED_OFF';
}
