<?php

declare(strict_types=1);

namespace App\Finance\Application\Command;

use App\Finance\Domain\Model\PaymentMethod;

/** The Admin records money received for an approved project and splits it among stages, petty cash and contingency. */
final readonly class RecordDeposit
{
    /**
     * @param list<Allocation> $allocations
     */
    public function __construct(
        public int $projectId,
        public int $actorId,
        public \DateTimeImmutable $date,
        public PaymentMethod $method,
        public ?string $reference,
        public ?string $note,
        public array $allocations,
    ) {
    }
}
