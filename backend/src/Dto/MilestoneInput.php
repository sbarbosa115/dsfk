<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class MilestoneInput
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Length(max: 200)]
        public ?string $name = null,

        /** Percentage of the stage, e.g. "25" or "12.5". */
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Regex(pattern: Patterns::PERCENT, message: 'Porcentaje inválido.')]
        #[Assert\Range(min: 0.01, max: 100)]
        public ?string $weight = null,

        public ?\DateTimeImmutable $plannedDate = null,
    ) {
    }

    public function weightInBasisPoints(): ?int
    {
        return null === $this->weight ? null : (int) round((float) $this->weight * 100);
    }
}
