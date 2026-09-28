<?php

declare(strict_types=1);

namespace App\Project\UI\Http\Input;

use App\Project\Domain\Model\ProjectRole;
use Symfony\Component\Validator\Constraints as Assert;

final class MemberInput
{
    public function __construct(
        #[Assert\Positive]
        public int $userId = 0,
        #[Assert\NotNull]
        public ?ProjectRole $role = null,
    ) {
    }
}
