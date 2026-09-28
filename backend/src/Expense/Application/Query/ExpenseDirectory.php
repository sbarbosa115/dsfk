<?php

declare(strict_types=1);

namespace App\Expense\Application\Query;

/** Expenses as other contexts see them. */
interface ExpenseDirectory
{
    /**
     * Spending that counts against the budget (approved and reimbursed).
     *
     * @return array<int, array<int, int>> stage id => category id => minor units
     */
    public function spending(int $projectId): array;

    public function usesCategory(int $categoryId): bool;

    /**
     * @param list<int> $movementIds
     *
     * @return array<int, int> expense id by its EXPENSE movement id
     */
    public function byMovements(array $movementIds): array;

    /** Who paid the expense (a Team Lead may open their own receipts). null: no such expense. */
    public function paidBy(int $expenseId): ?int;
}
