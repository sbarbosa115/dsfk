<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class VoidInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 2000)]
        public ?string $reason = null,
    ) {
    }
}
