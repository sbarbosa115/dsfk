<?php

namespace App\Dto;

use App\Enum\PaymentMethod;
use Symfony\Component\Validator\Constraints as Assert;

class DepositInput
{
    /**
     * @param list<AllocationInput> $allocations
     */
    public function __construct(
        #[Assert\NotNull]
        #[Assert\LessThanOrEqual('today', message: 'La fecha no puede estar en el futuro.')]
        public ?\DateTimeImmutable $date = null,

        #[Assert\NotNull]
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
