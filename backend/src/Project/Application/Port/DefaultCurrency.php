<?php

declare(strict_types=1);

namespace App\Project\Application\Port;

/** The currency for new projects, from the Settings context. */
interface DefaultCurrency
{
    public function code(): string;
}
