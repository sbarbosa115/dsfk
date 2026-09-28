<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

use App\Expense\Domain\Model\PayoutMethod;

/** The PM or an Admin pays approved Team Lead expenses back from the caja menor, in one movement. */
final readonly class ReimburseExpenses
{
    /**
     * @param list<int> $expenseIds
     */
    public function __construct(
        public int $projectId,
        public int $actorId,
        public array $expenseIds,
        public \DateTimeImmutable $date,
        public PayoutMethod $method,
        public ?string $reference,
    ) {
    }
}
