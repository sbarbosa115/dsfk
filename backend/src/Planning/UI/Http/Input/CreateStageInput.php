<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateStageInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $name = '',
        /** YYYY-MM-DD */
        #[Assert\Date]
        public ?string $plannedStart = null,
        /** YYYY-MM-DD */
        #[Assert\Date]
        public ?string $plannedEnd = null,
    ) {
    }
}
