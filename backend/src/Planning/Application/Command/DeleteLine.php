<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

final readonly class DeleteLine
{
    public function __construct(public int $lineId)
    {
    }
}
