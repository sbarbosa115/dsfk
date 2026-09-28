<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Input;

use App\Finance\Domain\Model\LedgerAccount;
use App\Shared\UI\Http\Patterns;
use Symfony\Component\Validator\Constraints as Assert;

final class AllocationInput
{
    public function __construct(
        #[Assert\NotNull(message: 'Elige el destino.')]
        public ?LedgerAccount $destination = null,
        /** Major units, e.g. "1500000.00". */
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::AMOUNT, message: 'Debe ser un monto positivo.')]
        public string $amount = '',
        /** Required when the destination is STAGE. */
        public ?int $stageId = null,
        /** Optional earmark, for STAGE only. */
        public ?int $categoryId = null,
    ) {
    }
}
