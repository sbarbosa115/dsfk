<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Output;

/** A stage, category or person an expense points at. */
final readonly class ExpenseRefOutput
{
    public function __construct(public int $id, public string $name)
    {
    }
}
