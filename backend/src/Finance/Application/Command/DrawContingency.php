<?php

declare(strict_types=1);

namespace App\Finance\Application\Command;

/** The Admin moves contingency money into a stage that ran short, with the reason. */
final readonly class DrawContingency
{
    public function __construct(
        public int $projectId,
        public int $actorId,
        public ?int $stageId,
        /** Major units. */
        public string $amount,
        public \DateTimeImmutable $date,
        public ?string $reason,
    ) {
    }
}
