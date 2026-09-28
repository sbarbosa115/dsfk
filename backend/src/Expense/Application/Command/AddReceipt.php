<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

/**
 * A receipt for an expense: its owner while it can still be corrected, or the PM or an Admin unless it was voided.
 * The file is the request's upload, already on disk.
 */
final readonly class AddReceipt
{
    public function __construct(
        public int $expenseId,
        public int $actorId,
        public bool $manager,
        public string $path,
        public string $originalName,
        public int $size,
    ) {
    }
}
