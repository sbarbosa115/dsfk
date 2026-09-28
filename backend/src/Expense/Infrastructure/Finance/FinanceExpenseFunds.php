<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Finance;

use App\Expense\Application\Port\ExpenseFunds;
use App\Finance\Application\Service\ExpensePayments;

final readonly class FinanceExpenseFunds implements ExpenseFunds
{
    public function __construct(private ExpensePayments $payments)
    {
    }

    public function pay(int $projectId, string $source, int $stageId, int $categoryId, int $amount, \DateTimeImmutable $date, string $description, int $actorId): int
    {
        return $this->payments->pay($projectId, $source, $stageId, $categoryId, $amount, $date, $description, $actorId);
    }

    public function refund(int $movementId, int $actorId, string $reason): void
    {
        $this->payments->refund($movementId, $actorId, $reason);
    }

    public function payBack(int $projectId, int $total, \DateTimeImmutable $date, string $note, int $actorId): int
    {
        return $this->payments->payBack($projectId, $total, $date, $note, $actorId);
    }
}
