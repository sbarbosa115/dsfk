<?php

declare(strict_types=1);

namespace App\Planning\Application\Port;

/** Whether money outside the plan (expenses, deposit earmarks) uses a category, so it cannot be deleted. */
interface CategoryUsage
{
    public function isUsed(int $categoryId): bool;
}
