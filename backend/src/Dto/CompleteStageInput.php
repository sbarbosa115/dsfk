<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CompleteStageInput
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\LessThanOrEqual('today', message: 'La fecha no puede estar en el futuro.')]
        public ?\DateTimeImmutable $actualEnd = null,
    ) {
    }
}
