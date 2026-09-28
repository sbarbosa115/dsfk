<?php

declare(strict_types=1);

namespace App\Planning\Infrastructure\Usage;

use App\Planning\Application\Port\CategoryUsage;

/** Until Finance and Expense exist, only budget lines use a category (checked by the repository). */
final class NoOtherCategoryUsage implements CategoryUsage
{
    public function isUsed(int $categoryId): bool
    {
        return false;
    }
}
