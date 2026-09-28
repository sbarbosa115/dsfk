<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Model\Category;
use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\Error\InvalidValue;

final readonly class AddCategoryHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans)
    {
    }

    public function __invoke(AddCategory $command): void
    {
        foreach ($this->plans->categoriesOf($command->projectId) as $category) {
            if ($category->isNamed($command->name)) {
                throw InvalidValue::field('name', 'Ya existe una categoría con ese nombre.');
            }
        }
        $this->plans->addCategory(new Category($command->projectId, $command->name));
    }
}
