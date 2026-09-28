<?php

declare(strict_types=1);

namespace App\Planning\Infrastructure\Finance;

use App\Expense\Application\Query\ExpenseDirectory;
use App\Finance\Application\Query\FinanceQueries;
use App\Planning\Application\Port\CategoryUsage;

/** A category stays while deposited money is earmarked for it or an expense uses it. */
final readonly class MoneyCategoryUsage implements CategoryUsage
{
    public function __construct(private FinanceQueries $finance, private ExpenseDirectory $expenses)
    {
    }

    public function isUsed(int $categoryId): bool
    {
        return $this->finance->usesCategory($categoryId) || $this->expenses->usesCategory($categoryId);
    }
}
