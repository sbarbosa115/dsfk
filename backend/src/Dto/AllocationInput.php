<?php

namespace App\Dto;

use App\Enum\LedgerAccount;
use Symfony\Component\Validator\Constraints as Assert;

class AllocationInput
{
    public function __construct(
        #[Assert\NotNull]
        public ?LedgerAccount $destination = null,

        /** Required when the destination is STAGE. */
        public ?int $stageId = null,

        /** Optional earmark, only for STAGE. */
        public ?int $categoryId = null,

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::AMOUNT, message: 'Debe ser un monto positivo.')]
        #[Assert\Positive]
        public ?string $amount = null,
    ) {
    }
}
