<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ContingencyInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::AMOUNT, message: 'Debe ser un monto positivo.')]
        public ?string $contingency = null,
    ) {
    }
}
