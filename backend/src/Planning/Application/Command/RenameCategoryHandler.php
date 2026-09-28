<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\Error\InvalidValue;

final readonly class RenameCategoryHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans)
    {
    }

    public function __invoke(RenameCategory $command): void
    {
        $category = $this->plans->category($command->categoryId);
        foreach ($this->plans->categoriesOf($category->getProjectId()) as $other) {
            if ($other !== $category && $other->isNamed($command->name)) {
                throw InvalidValue::field('name', 'Ya existe una categoría con ese nombre.');
            }
        }
        $category->rename($command->name);
    }
}
