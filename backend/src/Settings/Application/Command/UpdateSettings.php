<?php

declare(strict_types=1);

namespace App\Settings\Application\Command;

/** A null field is left as it is. */
final readonly class UpdateSettings
{
    /**
     * @param list<int>|null $budgetWarningPercents
     */
    public function __construct(
        public ?string $defaultCurrency = null,
        public ?string $teamLeadExpenseLimit = null,
        public ?int $pettyCashLowBalancePercent = null,
        public ?array $budgetWarningPercents = null,
    ) {
    }
}
