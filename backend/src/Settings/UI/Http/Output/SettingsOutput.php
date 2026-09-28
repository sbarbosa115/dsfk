<?php

declare(strict_types=1);

namespace App\Settings\UI\Http\Output;

use App\Settings\Domain\Model\AppSettings;

final readonly class SettingsOutput
{
    /**
     * @param list<int> $budgetWarningPercents
     */
    public function __construct(
        /** ISO 4217 code for new projects */
        public string $defaultCurrency,
        /** Major units; a Team Lead expense above it also needs an Admin's approval */
        public string $teamLeadExpenseLimit,
        /** Caja menor alert when the balance drops below this % of the last top-up */
        public int $pettyCashLowBalancePercent,
        /** Budget alert thresholds, % of a stage or category budget, ascending */
        public array $budgetWarningPercents,
    ) {
    }

    public static function from(AppSettings $settings): self
    {
        return new self($settings->defaultCurrency, $settings->teamLeadExpenseLimit, $settings->pettyCashLowBalancePercent, $settings->budgetWarningPercents);
    }
}
