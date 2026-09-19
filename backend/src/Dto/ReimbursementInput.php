<?php

namespace App\Dto;

use App\Enum\PaymentMethod;
use Symfony\Component\Validator\Constraints as Assert;

class ReimbursementInput
{
    /**
     * @param list<int> $expenseIds
     */
    public function __construct(
        #[Assert\Count(min: 1, minMessage: 'Selecciona al menos un gasto.')]
        #[Assert\All([new Assert\Type('int')])]
        public array $expenseIds = [],

        #[Assert\NotNull]
        #[Assert\LessThanOrEqual('today', message: 'La fecha no puede estar en el futuro.')]
        public ?\DateTimeImmutable $date = null,

        #[Assert\NotNull]
        public ?PaymentMethod $method = null,

        #[Assert\Length(max: 100)]
        public ?string $reference = null,
    ) {
    }
}
