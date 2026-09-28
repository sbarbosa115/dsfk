<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class BudgetEventOutput
{
    public function __construct(
        #[OA\Property(enum: ['DRAFT', 'SUBMITTED', 'RETURNED', 'APPROVED'])]
        public string $status,
        public PersonOutput $user,
        public ?string $comment,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
