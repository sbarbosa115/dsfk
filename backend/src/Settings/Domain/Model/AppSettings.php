<?php

declare(strict_types=1);

namespace App\Settings\Domain\Model;

/**
 * The global settings, with their defaults. Stored as one row per key (Setting); a key that is not stored uses
 * its default, and a stored key that is no longer known is ignored.
 */
final readonly class AppSettings
{
    public const DEFAULT_CURRENCY = 'default_currency';
    /** Major units in the project currency; above it an Admin approves too. */
    public const TEAM_LEAD_EXPENSE_LIMIT = 'team_lead_expense_limit';
    public const PETTY_CASH_LOW_BALANCE_PERCENT = 'petty_cash_low_balance_percent';
    public const BUDGET_WARNING_PERCENTS = 'budget_warning_percents';

    /**
     * @param list<int> $budgetWarningPercents ascending, unique
     */
    private function __construct(
        public string $defaultCurrency,
        public string $teamLeadExpenseLimit,
        public int $pettyCashLowBalancePercent,
        public array $budgetWarningPercents,
    ) {
    }

    /**
     * @param array<string, mixed> $stored key => value
     */
    public static function fromStored(array $stored): self
    {
        $currency = $stored[self::DEFAULT_CURRENCY] ?? null;
        $limit = $stored[self::TEAM_LEAD_EXPENSE_LIMIT] ?? null;
        $lowBalance = $stored[self::PETTY_CASH_LOW_BALANCE_PERCENT] ?? null;
        $warnings = $stored[self::BUDGET_WARNING_PERCENTS] ?? null;

        return new self(
            \is_string($currency) ? $currency : 'COP',
            \is_string($limit) || \is_int($limit) ? (string) $limit : '500000',
            \is_int($lowBalance) ? $lowBalance : 20,
            \is_array($warnings) ? self::thresholds($warnings) : [80, 100],
        );
    }

    /**
     * @param list<int>|null $budgetWarningPercents
     */
    public function with(
        ?string $defaultCurrency = null,
        ?string $teamLeadExpenseLimit = null,
        ?int $pettyCashLowBalancePercent = null,
        ?array $budgetWarningPercents = null,
    ): self {
        return new self(
            $defaultCurrency ?? $this->defaultCurrency,
            $teamLeadExpenseLimit ?? $this->teamLeadExpenseLimit,
            $pettyCashLowBalancePercent ?? $this->pettyCashLowBalancePercent,
            null === $budgetWarningPercents ? $this->budgetWarningPercents : self::thresholds($budgetWarningPercents),
        );
    }

    /**
     * @return array<string, mixed> key => value, as stored
     */
    public function toStored(): array
    {
        return [
            self::DEFAULT_CURRENCY => $this->defaultCurrency,
            self::TEAM_LEAD_EXPENSE_LIMIT => $this->teamLeadExpenseLimit,
            self::PETTY_CASH_LOW_BALANCE_PERCENT => $this->pettyCashLowBalancePercent,
            self::BUDGET_WARNING_PERCENTS => $this->budgetWarningPercents,
        ];
    }

    /**
     * @param array<mixed> $percents
     *
     * @return list<int>
     */
    private static function thresholds(array $percents): array
    {
        $percents = array_values(array_unique(array_map(intval(...), array_filter($percents, is_numeric(...)))));
        sort($percents);

        return $percents;
    }
}
