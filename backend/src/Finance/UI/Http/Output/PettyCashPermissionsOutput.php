<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

final readonly class PettyCashPermissionsOutput
{
    public function __construct(public bool $close, public bool $signOff)
    {
    }
}
