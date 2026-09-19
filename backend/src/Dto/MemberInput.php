<?php

namespace App\Dto;

use App\Enum\ProjectRole;
use Symfony\Component\Validator\Constraints as Assert;

class MemberInput
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public ?int $userId = null,

        #[Assert\NotNull]
        public ?ProjectRole $role = null,
    ) {
    }
}
