<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Output;

use App\Shared\UI\Http\PageOutput;

/**
 * @extends PageOutput<ExpenseOutput>
 */
final readonly class ExpensePageOutput extends PageOutput
{
    /**
     * @param list<ExpenseOutput> $items
     */
    public function __construct(array $items, int $total, int $page, int $perPage, public ExpenseSummaryOutput $summary)
    {
        parent::__construct($items, $total, $page, $perPage);
    }
}
