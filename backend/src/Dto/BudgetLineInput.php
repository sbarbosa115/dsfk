<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class BudgetLineInput
{
    public function __construct(
        #[Assert\NotNull]
        public ?int $categoryId = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public ?string $description = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        public ?string $unit = null,

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::QUANTITY, message: 'Cantidad inválida (máximo 3 decimales).')]
        #[Assert\Positive]
        public ?string $quantity = null,

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::AMOUNT, message: 'Debe ser un monto positivo.')]
        public ?string $unitPrice = null,
    ) {
    }
}
