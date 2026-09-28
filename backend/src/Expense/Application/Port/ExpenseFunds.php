<?php

declare(strict_types=1);

namespace App\Expense\Application\Port;

use App\Shared\Domain\Error\InvalidValue;

/** The Finance context: where the money of an expense comes from and goes back to. */
interface ExpenseFunds
{
    /**
     * @param 'STAGE'|'PETTY_CASH' $source
     *
     * @return int the movement's id
     *
     * @throws InvalidValue insufficient_funds
     */
    public function pay(int $projectId, string $source, int $stageId, int $categoryId, int $amount, \DateTimeImmutable $date, string $description, int $actorId): int;

    public function refund(int $movementId, int $actorId, string $reason): void;

    /**
     * @return int the movement's id
     *
     * @throws InvalidValue insufficient_funds
     */
    public function payBack(int $projectId, int $total, \DateTimeImmutable $date, string $note, int $actorId): int;
}
