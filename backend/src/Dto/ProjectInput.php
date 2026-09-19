<?php

namespace App\Dto;

use App\Enum\ProjectStatus;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectInput
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Length(max: 150)]
        public ?string $name = null,

        public ?string $description = null,

        #[Assert\Currency]
        public ?string $currency = null,

        public ?ProjectStatus $status = null,

        public ?\DateTimeImmutable $plannedStart = null,

        #[Assert\GreaterThanOrEqual(propertyPath: 'plannedStart')]
        public ?\DateTimeImmutable $plannedEnd = null,
    ) {
    }
}
