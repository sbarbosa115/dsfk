<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

final readonly class RenameCategory
{
    public function __construct(public int $categoryId, public string $name)
    {
    }
}
