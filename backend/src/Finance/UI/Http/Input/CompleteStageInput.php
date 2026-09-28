<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class CompleteStageInput
{
    public function __construct(
        /** YYYY-MM-DD, not in the future nor before the stage started. */
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $actualEnd = '',
    ) {
    }
}
