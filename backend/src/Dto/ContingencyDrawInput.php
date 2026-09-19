<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ContingencyDrawInput
{
    public function __construct(
        #[Assert\NotNull]
        public ?int $stageId = null,

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::AMOUNT, message: 'Debe ser un monto positivo.')]
        #[Assert\Positive]
        public ?string $amount = null,

        #[Assert\NotNull]
        #[Assert\LessThanOrEqual('today', message: 'La fecha no puede estar en el futuro.')]
        public ?\DateTimeImmutable $date = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 2000)]
        public ?string $reason = null,
    ) {
    }
}
