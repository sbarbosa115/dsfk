<?php

namespace App\Dto;

final class Patterns
{
    public const AMOUNT = '/^\d{1,13}(\.\d{1,2})?$/';
    public const QUANTITY = '/^\d{1,11}(\.\d{1,3})?$/';
    public const PERCENT = '/^\d{1,3}(\.\d{1,2})?$/';
}
