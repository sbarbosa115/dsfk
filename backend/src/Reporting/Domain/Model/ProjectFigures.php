<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Model;

/**
 * Earned value of a project as of a day. Money in minor units, shares in basis points.
 *
 * - budget (BAC): the stages' approved budget, contingency left out
 * - earned (EV): BAC × physical progress
 * - planned (PV): what should have been earned by the day, from the planned dates
 * - spent (AC): approved spending
 * - cpi = EV / AC (below 1: spending more than the progress justifies); spi = EV / PV (below 1: behind)
 * - forecast (EAC) = BAC / CPI; varianceAtCompletion = BAC − EAC
 */
final readonly class ProjectFigures
{
    /**
     * @param list<StageFigures> $stages
     */
    public function __construct(
        public int $budget,
        public int $earned,
        public int $planned,
        public int $spent,
        public ?float $cpi,
        public ?float $spi,
        public ?int $forecast,
        public ?int $varianceAtCompletion,
        public int $plannedProgress,
        public int $executed,
        public array $stages,
    ) {
    }
}
