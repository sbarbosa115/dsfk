<?php

declare(strict_types=1);

namespace App\Planning\Infrastructure\Finance;

use App\Finance\Application\Query\FinanceQueries;
use App\Planning\Application\Port\CategoryUsage;

/** A category that earmarks deposited money stays (expenses join in with the Expense context). */
final readonly class LedgerCategoryUsage implements CategoryUsage
{
    public function __construct(private FinanceQueries $finance)
    {
    }

    public function isUsed(int $categoryId): bool
    {
        return $this->finance->usesCategory($categoryId);
    }
}
