<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Input;

use App\Finance\Domain\Model\PaymentMethod;
use Symfony\Component\Validator\Constraints as Assert;

final class DepositInput
{
    /**
     * @param list<AllocationInput> $allocations
     */
    public function __construct(
        /** YYYY-MM-DD, not in the future. */
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date = '',
        #[Assert\NotNull(message: 'Elige el medio de pago.')]
        public ?PaymentMethod $method = null,
        #[Assert\Length(max: 100)]
        public ?string $reference = null,
        #[Assert\Length(max: 2000)]
        public ?string $note = null,
        #[Assert\Count(min: 1, max: 30, minMessage: 'Agrega al menos una distribución.')]
        #[Assert\Valid]
        public array $allocations = [],
    ) {
    }
}
