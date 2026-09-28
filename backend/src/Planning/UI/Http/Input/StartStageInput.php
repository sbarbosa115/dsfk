<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class StartStageInput
{
    public function __construct(
        /** YYYY-MM-DD, not in the future. */
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $actualStart = '',
    ) {
    }
}
