<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Input;

use App\Shared\UI\Http\Patterns;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateMilestoneInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 200)]
        public string $name = '',
        /** Percentage of the stage, e.g. "25" or "12.5". */
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::PERCENT, message: 'Invalid percentage (2 decimals at most).')]
        public string $weight = '',
        #[Assert\Date]
        public ?string $plannedDate = null,
    ) {
    }
}
