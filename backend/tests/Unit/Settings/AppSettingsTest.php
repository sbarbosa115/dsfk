<?php

declare(strict_types=1);

namespace App\Tests\Unit\Settings;

use App\Settings\Domain\Model\AppSettings;
use PHPUnit\Framework\TestCase;

final class AppSettingsTest extends TestCase
{
    public function testDefaultsApplyWhenNothingIsStored(): void
    {
        $settings = AppSettings::fromStored([]);

        self::assertSame('COP', $settings->defaultCurrency);
        self::assertSame('500000', $settings->teamLeadExpenseLimit);
        self::assertSame(20, $settings->pettyCashLowBalancePercent);
        self::assertSame([80, 100], $settings->budgetWarningPercents);
    }

    public function testStoredValuesWinAndUnknownKeysAreIgnored(): void
    {
        $settings = AppSettings::fromStored(['default_currency' => 'USD', 'retired_key' => 1]);

        self::assertSame('USD', $settings->defaultCurrency);
        self::assertSame(20, $settings->pettyCashLowBalancePercent);
    }

    public function testWarningThresholdsAreKeptSortedAndUnique(): void
    {
        $settings = AppSettings::fromStored([])->with(budgetWarningPercents: [100, 75, 75]);

        self::assertSame([75, 100], $settings->budgetWarningPercents);
    }

    public function testChangingOneValueKeepsTheOthers(): void
    {
        $settings = AppSettings::fromStored([])->with(teamLeadExpenseLimit: '750000');

        self::assertSame('750000', $settings->teamLeadExpenseLimit);
        self::assertSame('COP', $settings->defaultCurrency);
        self::assertSame(['default_currency' => 'COP', 'team_lead_expense_limit' => '750000', 'petty_cash_low_balance_percent' => 20, 'budget_warning_percents' => [80, 100]], $settings->toStored());
    }
}
