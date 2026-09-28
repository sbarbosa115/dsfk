<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Application\Port\CategoryUsage;
use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\Error\Conflict;

final readonly class DeleteCategoryHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans, private CategoryUsage $usage)
    {
    }

    public function __invoke(DeleteCategory $command): void
    {
        $category = $this->plans->category($command->categoryId);
        if ($this->plans->categoryHasLines($category) || $this->usage->isUsed($command->categoryId)) {
            throw new Conflict('category_in_use');
        }
        $this->plans->removeCategory($category);
    }
}
