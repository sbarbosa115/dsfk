<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CompleteMilestoneInput
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\LessThanOrEqual('today', message: 'La fecha no puede estar en el futuro.')]
        public ?\DateTimeImmutable $completedAt = null,

        #[Assert\Length(max: 2000)]
        public ?string $notes = null,
    ) {
    }
}
