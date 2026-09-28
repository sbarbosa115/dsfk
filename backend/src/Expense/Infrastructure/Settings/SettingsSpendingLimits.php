<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Settings;

use App\Expense\Application\Port\SpendingLimits;
use App\Project\Application\Query\ProjectDirectory;
use App\Settings\Application\Query\SettingsQueries;
use App\Shared\Domain\Money\MinorUnits;

final readonly class SettingsSpendingLimits implements SpendingLimits
{
    public function __construct(private SettingsQueries $settings, private ProjectDirectory $projects)
    {
    }

    public function teamLeadLimit(int $projectId): int
    {
        return MinorUnits::fromMajor($this->settings->current()->teamLeadExpenseLimit, $this->currency($projectId));
    }

    public function currency(int $projectId): string
    {
        return $this->projects->info($projectId)->currency;
    }
}
