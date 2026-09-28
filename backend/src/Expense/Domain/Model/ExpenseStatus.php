<?php

declare(strict_types=1);

namespace App\Expense\Domain\Model;

enum ExpenseStatus: string
{
    /** A Team Lead's expense waiting for the PM. */
    case Submitted = 'SUBMITTED';
    /** Approved by the PM but above the Team Lead limit: waiting for the Admin. */
    case PmApproved = 'PM_APPROVED';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    /** A Team Lead's expense paid back from petty cash. */
    case Reimbursed = 'REIMBURSED';
    case Voided = 'VOIDED';

    /** Counts against the budget. */
    public function isSpent(): bool
    {
        return self::Approved === $this || self::Reimbursed === $this;
    }

    /**
     * @return list<self>
     */
    public static function spent(): array
    {
        return [self::Approved, self::Reimbursed];
    }
}
