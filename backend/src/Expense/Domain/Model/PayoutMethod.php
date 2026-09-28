<?php

declare(strict_types=1);

namespace App\Expense\Domain\Model;

/** How a reimbursement reached the Team Lead (same values as Finance's payment methods). */
enum PayoutMethod: string
{
    case Transfer = 'TRANSFER';
    case Cash = 'CASH';
    case Check = 'CHECK';
    case Other = 'OTHER';
}
