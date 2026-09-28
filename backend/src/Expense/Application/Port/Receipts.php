<?php

declare(strict_types=1);

namespace App\Expense\Application\Port;

/** The Document context: an expense is approved only with a receipt. */
interface Receipts
{
    public function has(int $expenseId): bool;
}
