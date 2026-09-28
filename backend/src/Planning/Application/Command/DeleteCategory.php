<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

/** Only a category nothing uses (no budget line, no expense, no earmark) can be deleted. */
final readonly class DeleteCategory
{
    public function __construct(public int $categoryId)
    {
    }
}
