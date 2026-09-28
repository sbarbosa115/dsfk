<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

final readonly class SetContingency
{
    public function __construct(
        public int $projectId,
        /** Major units of the project's currency. */
        public string $amount,
    ) {
    }
}
