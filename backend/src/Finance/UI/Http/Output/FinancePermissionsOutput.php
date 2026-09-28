<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

final readonly class FinancePermissionsOutput
{
    public function __construct(
        public bool $deposit,
        public bool $void,
        public bool $drawContingency,
        public bool $completeStages,
    ) {
    }
}
