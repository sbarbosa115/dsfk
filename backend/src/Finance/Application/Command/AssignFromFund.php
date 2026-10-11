<?php

declare(strict_types=1);

namespace App\Finance\Application\Command;

use App\Finance\Domain\Model\LedgerAccount;

/** The Admin gives project fund money to an open stage or to the caja menor. */
final readonly class AssignFromFund
{
    public function __construct(
        public int $projectId,
        public int $actorId,
        public LedgerAccount $destination,
        public ?int $stageId,
        /** Major units. */
        public string $amount,
        public \DateTimeImmutable $date,
        public ?string $note,
    ) {
    }
}
