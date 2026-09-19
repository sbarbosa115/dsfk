<?php

namespace App\Enum;

enum MovementType: string
{
    case Deposit = 'DEPOSIT';
    case ContingencyDraw = 'CONTINGENCY_DRAW';
    /** Leftover of a completed stage moved to the next one. */
    case Carryover = 'CARRYOVER';
    /** Expense paid from a stage balance or the caja menor. */
    case Expense = 'EXPENSE';
    /** Team Lead expenses paid back from the caja menor. */
    case Reimbursement = 'REIMBURSEMENT';

    /** Movements that bring money into the project or move it between accounts. */
    public function isFunding(): bool
    {
        return \in_array($this, [self::Deposit, self::ContingencyDraw, self::Carryover], true);
    }
}
