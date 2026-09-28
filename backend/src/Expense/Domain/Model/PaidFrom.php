<?php

declare(strict_types=1);

namespace App\Expense\Domain\Model;

enum PaidFrom: string
{
    /** From the stage's balance (PM or Admin). */
    case Stage = 'STAGE';
    /** From the caja menor (PM or Admin). */
    case PettyCash = 'PETTY_CASH';
    /** A Team Lead's own money, paid back later from the caja menor. */
    case OutOfPocket = 'OUT_OF_POCKET';
}
