<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CloseCycleInput
{
    public function __construct(
        #[Assert\Length(max: 2000)]
        public ?string $note = null,
    ) {
    }
}
