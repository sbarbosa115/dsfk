<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

/** Shape checks shared by Input DTOs. Amounts travel as decimal strings in major units. */
final class Patterns
{
    public const AMOUNT = '/^\d{1,13}(\.\d{1,2})?$/';
    public const QUANTITY = '/^\d{1,11}(\.\d{1,3})?$/';
    public const PERCENT = '/^\d{1,3}(\.\d{1,2})?$/';
    public const DATE = '/^\d{4}-\d{2}-\d{2}$/';
}
