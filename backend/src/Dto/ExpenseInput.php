<?php

namespace App\Dto;

use App\Enum\PaidFrom;
use Symfony\Component\Validator\Constraints as Assert;

class ExpenseInput
{
    public function __construct(
        #[Assert\NotNull]
        public ?int $stageId = null,

        #[Assert\NotNull]
        public ?int $categoryId = null,

        #[Assert\NotNull]
        #[Assert\LessThanOrEqual('today', message: 'La fecha no puede estar en el futuro.')]
        public ?\DateTimeImmutable $date = null,

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::AMOUNT, message: 'Debe ser un monto positivo.')]
        #[Assert\Positive]
        public ?string $amount = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public ?string $description = null,

        #[Assert\Length(max: 150)]
        public ?string $supplier = null,

        #[Assert\Length(max: 60)]
        public ?string $invoiceNumber = null,

        /** Ignored on edit. */
        public ?PaidFrom $paidFrom = null,
    ) {
    }
}
