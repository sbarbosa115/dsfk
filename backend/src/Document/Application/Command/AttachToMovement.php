<?php

declare(strict_types=1);

namespace App\Document\Application\Command;

/** A file (a proof of deposit) for a Finance movement. The caller has checked the movement and the access. */
final readonly class AttachToMovement
{
    public function __construct(public int $projectId, public int $movementId, public Upload $file, public int $actorId)
    {
    }
}
