<?php

namespace App\Enum;

enum BudgetStatus: string
{
    case Draft = 'DRAFT';
    case Submitted = 'SUBMITTED';
    /** Sent back by the Admin with a comment; editable again like a draft. */
    case Returned = 'RETURNED';
    /** Locked for good: the baseline everything is compared against. */
    case Approved = 'APPROVED';

    public function isEditable(): bool
    {
        return self::Draft === $this || self::Returned === $this;
    }
}
