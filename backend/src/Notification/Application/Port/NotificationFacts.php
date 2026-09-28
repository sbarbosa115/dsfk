<?php

declare(strict_types=1);

namespace App\Notification\Application\Port;

/** What the emails say, read from the contexts that know it. Money in minor units. */
interface NotificationFacts
{
    /**
     * @return array{id: int, name: string, currency: string}
     */
    public function project(int $projectId): array;

    public function personName(int $userId): string;

    /**
     * @return array{projectId: int, stageId: int, categoryId: int, stage: string, category: string, amount: int, description: string, paidById: int, paidBy: string, rejectionReason: ?string}|null
     */
    public function expense(int $expenseId): ?array;

    /**
     * The budget and the approved spending of the expense's stage and of its category.
     *
     * @return array{stage: array{budget: int, spent: int}, category: array{budget: int, spent: int}}
     */
    public function budgetUse(int $projectId, int $stageId, int $categoryId): array;

    /**
     * @return list<int> the budget percentages that warn, ascending
     */
    public function budgetPercents(): array;

    public function pettyCashLowPercent(): int;

    /**
     * @return array{balance: int, lastTopUp: int}
     */
    public function pettyCash(int $projectId): array;

    /**
     * @return array{projectId: int, number: int, closingBalance: int, closedById: ?int, note: ?string}|null
     */
    public function cycle(int $cycleId): ?array;

    /**
     * @return list<int> ids of the ACTIVE projects
     */
    public function activeProjects(): array;

    /**
     * @return array{overdue: list<array{stage: string, name: string, plannedDate: \DateTimeImmutable}>, pending: int, toReimburse: int, unsigned: int}
     */
    public function digest(int $projectId, \DateTimeImmutable $today): array;
}
