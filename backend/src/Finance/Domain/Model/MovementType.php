<?php

declare(strict_types=1);

namespace App\Finance\Domain\Model;

enum MovementType: string
{
    case Deposit = 'DEPOSIT';
    case ContingencyDraw = 'CONTINGENCY_DRAW';
    /** Leftover of a completed stage moved to the next one (or to the contingency). */
    case Carryover = 'CARRYOVER';
    /** Expense paid from a stage balance or petty cash. */
    case Expense = 'EXPENSE';
    /** Team Lead expenses paid back from petty cash. */
    case Reimbursement = 'REIMBURSEMENT';

    /** Movements that bring money into the project or move it between accounts (the Finance tab's list). */
    public function isFunding(): bool
    {
        return \in_array($this, self::funding(), true);
    }

    /**
     * @return list<self>
     */
    public static function funding(): array
    {
        return [self::Deposit, self::ContingencyDraw, self::Carryover];
    }
}
