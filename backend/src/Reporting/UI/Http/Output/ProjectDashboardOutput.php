<?php

declare(strict_types=1);

namespace App\Reporting\UI\Http\Output;

/**
 * A project's health: earned value (EV), planned value (PV), CPI = EV / spent, SPI = EV / PV, the forecast final
 * cost (BAC / CPI), money in and out per month, and what needs attention. Money in major units, shares in basis
 * points.
 */
final readonly class ProjectDashboardOutput
{
    /**
     * @param list<StageHealthOutput>    $stages
     * @param list<MonthFlowOutput>      $monthly the last twelve months
     * @param list<DashboardAlertOutput> $alerts
     */
    public function __construct(
        public string $currency,
        public bool $budgetApproved,
        public DashboardTotalsOutput $totals,
        public int $progress,
        public int $plannedProgress,
        public int $executed,
        public string $earnedValue,
        public string $plannedValue,
        public ?float $cpi,
        public ?float $spi,
        public ?string $forecastAtCompletion,
        public ?string $varianceAtCompletion,
        public array $stages,
        public array $monthly,
        public array $alerts,
    ) {
    }
}
