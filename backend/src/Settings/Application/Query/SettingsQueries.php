<?php

declare(strict_types=1);

namespace App\Settings\Application\Query;

use App\Settings\Domain\Model\AppSettings;

/** What other contexts read: the default currency, the Team Lead limit, alert thresholds. */
interface SettingsQueries
{
    public function current(): AppSettings;
}
