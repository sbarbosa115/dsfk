<?php

namespace App\Enum;

enum PaidFrom: string
{
    case Stage = 'STAGE';
    case PettyCash = 'PETTY_CASH';
    /** Paid by a Team Lead with their own money; reimbursed later from the caja menor. */
    case OutOfPocket = 'OUT_OF_POCKET';
}
