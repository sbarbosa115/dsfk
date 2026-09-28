<?php

declare(strict_types=1);

namespace App\Reporting\Application\Service;

/** Something on a project that needs attention; the UI words it from its code. */
final readonly class DashboardAlert
{
    public function __construct(
        /** error, warning or info */
        public string $level,
        /** stage_over_budget, stage_near_budget, stage_delayed, milestones_overdue, expenses_pending, expenses_to_reimburse, cycles_unsigned */
        public string $code,
        public ?string $stage = null,
        /** Basis points. */
        public ?int $executed = null,
        public ?\DateTimeImmutable $plannedEnd = null,
        public ?int $count = null,
    ) {
    }
}
