<?php

declare(strict_types=1);

namespace App\Document\Application\Query;

final readonly class AttachmentView
{
    public function __construct(
        public int $id,
        public int $projectId,
        public ?int $movementId,
        public ?int $expenseId,
        public string $name,
        public string $mimeType,
        public int $size,
        public string $storedName,
    ) {
    }
}
