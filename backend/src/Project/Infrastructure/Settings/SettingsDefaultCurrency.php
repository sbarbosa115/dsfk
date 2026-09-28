<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Settings;

use App\Project\Application\Port\DefaultCurrency;
use App\Settings\Application\Query\SettingsQueries;

final readonly class SettingsDefaultCurrency implements DefaultCurrency
{
    public function __construct(private SettingsQueries $settings)
    {
    }

    public function code(): string
    {
        return $this->settings->current()->defaultCurrency;
    }
}
