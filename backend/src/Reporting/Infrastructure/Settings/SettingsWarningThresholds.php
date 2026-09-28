<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Settings;

use App\Reporting\Application\Port\WarningThresholds;
use App\Settings\Application\Query\SettingsQueries;

final readonly class SettingsWarningThresholds implements WarningThresholds
{
    public function __construct(private SettingsQueries $settings)
    {
    }

    public function budgetPercents(): array
    {
        return $this->settings->current()->budgetWarningPercents;
    }
}
