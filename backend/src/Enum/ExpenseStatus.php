<?php

namespace App\Enum;

enum ExpenseStatus: string
{
    /** Team Lead expense waiting for the PM. */
    case Submitted = 'SUBMITTED';
    /** Approved by the PM but above the Team Lead limit: waiting for the Admin. */
    case PmApproved = 'PM_APPROVED';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    /** Team Lead expense paid back from the caja menor. */
    case Reimbursed = 'REIMBURSED';
    case Voided = 'VOIDED';

    /** Counts against the budget. */
    public function isSpent(): bool
    {
        return self::Approved === $this || self::Reimbursed === $this;
    }
}
