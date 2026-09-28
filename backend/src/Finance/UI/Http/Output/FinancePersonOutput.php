<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

final readonly class FinancePersonOutput
{
    public function __construct(public int $id, public string $fullName)
    {
    }
}
