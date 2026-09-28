<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

use App\Expense\Domain\Model\PaidFrom;

/**
 * Someone in the project records money spent. The PM and Admins (`$manager`) pay from a stage or the caja menor;
 * a Team Lead paid it out of pocket.
 */
final readonly class RecordExpense
{
    public function __construct(
        public int $projectId,
        public int $actorId,
        public bool $manager,
        public ExpenseDetails $details,
        public ?PaidFrom $paidFrom,
    ) {
    }
}
