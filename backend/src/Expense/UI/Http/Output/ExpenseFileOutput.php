<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Output;

final readonly class ExpenseFileOutput
{
    public function __construct(public int $id, public string $name, public string $mimeType, public int $size)
    {
    }
}
