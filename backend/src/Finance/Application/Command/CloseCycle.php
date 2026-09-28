<?php

declare(strict_types=1);

namespace App\Finance\Application\Command;

/** Petty cash's current cycle is closed with its balance; the next use opens the next one with that balance. */
final readonly class CloseCycle
{
    public function __construct(public int $projectId, public int $actorId, public ?string $note)
    {
    }
}
