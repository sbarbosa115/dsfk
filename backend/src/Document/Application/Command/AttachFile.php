<?php

declare(strict_types=1);

namespace App\Document\Application\Command;

/**
 * A file for a Finance movement (a proof of deposit) or an expense (a receipt): exactly one of the two. The caller
 * has checked the record and the access.
 */
final readonly class AttachFile
{
    private function __construct(public int $projectId, public ?int $movementId, public ?int $expenseId, public Upload $file, public int $actorId)
    {
    }

    public static function toMovement(int $projectId, int $movementId, Upload $file, int $actorId): self
    {
        return new self($projectId, $movementId, null, $file, $actorId);
    }

    public static function toExpense(int $projectId, int $expenseId, Upload $file, int $actorId): self
    {
        return new self($projectId, null, $expenseId, $file, $actorId);
    }
}
