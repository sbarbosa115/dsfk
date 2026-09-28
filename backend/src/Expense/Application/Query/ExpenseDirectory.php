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
     * Spending (approved and reimbursed) per month from `$from` on, by the expense's date.
     *
     * @return array<string, int> minor units by YYYY-MM
     */
    public function monthlySpending(int $projectId, \DateTimeImmutable $from): array;

    /** Team Lead expenses waiting for the PM or an Admin. */
    public function pendingCount(int $projectId): int;

    /** Approved Team Lead expenses not paid back yet. */
    public function toReimburseCount(int $projectId): int;

    /**
     * What a notification says about an expense. null: no such expense.
     *
     * @return array{projectId: int, stageId: int, categoryId: int, amount: int, description: string, paidById: int, rejectionReason: ?string}|null
     */
    public function facts(int $expenseId): ?array;

    /**
     * @param list<int> $movementIds
     *
     * @return array<int, int> expense id by its EXPENSE movement id
     */
    public function byMovements(array $movementIds): array;

    /** Who paid the expense (a Team Lead may open their own receipts). null: no such expense. */
    public function paidBy(int $expenseId): ?int;
}
