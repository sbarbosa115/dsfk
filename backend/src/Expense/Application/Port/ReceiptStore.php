<?php

declare(strict_types=1);

namespace App\Expense\Application\Port;

/** The Document context keeps receipts (it checks the file's type and size). */
interface ReceiptStore
{
    public function attach(int $projectId, int $expenseId, string $path, string $originalName, int $size, int $actorId): void;
}
