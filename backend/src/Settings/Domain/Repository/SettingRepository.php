<?php

declare(strict_types=1);

namespace App\Settings\Domain\Repository;

use App\Settings\Domain\Model\AppSettings;

interface SettingRepository
{
    public function current(): AppSettings;

    public function save(AppSettings $settings): void;
}
