<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class StageInput
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Length(max: 150)]
        public ?string $name = null,

        public ?\DateTimeImmutable $plannedStart = null,

        #[Assert\GreaterThanOrEqual(propertyPath: 'plannedStart')]
        public ?\DateTimeImmutable $plannedEnd = null,
    ) {
    }
}
