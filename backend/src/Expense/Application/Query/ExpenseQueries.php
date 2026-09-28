<?php

declare(strict_types=1);

namespace App\Expense\Application\Query;

use App\Expense\Domain\Model\Expense;
use App\Expense\Domain\Model\ExpenseStatus;
use App\Shared\Application\Query\Page;

interface ExpenseQueries
{
    /** The project an expense belongs to, so a controller checks access first. null: no such expense. */
    public function projectOf(int $expenseId): ?int;

    /**
     * Newest first. `$paidBy` narrows to one person's expenses (a Team Lead sees only theirs); `$search` looks in
     * the description, supplier and invoice number.
     *
     * @param list<ExpenseStatus> $statuses empty: all
     *
     * @return Page<Expense>
     */
    public function page(int $projectId, ?int $paidBy, ?string $search, array $statuses, ?int $stageId, int $page, int $perPage): Page;

    /**
     * Team Lead expenses (out of pocket) per status: count and minor units.
     *
     * @return array<string, array{count: int, total: int}>
     */
    public function outOfPocketByStatus(int $projectId, ?int $paidBy): array;
}
