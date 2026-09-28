<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Input;

use App\Shared\UI\Http\Patterns;
use Symfony\Component\Validator\Constraints as Assert;

final class DrawInput
{
    public function __construct(
        #[Assert\NotNull(message: 'Choose the stage.')]
        public ?int $stageId = null,
        /** Major units. */
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::AMOUNT, message: 'It must be a positive amount.')]
        public string $amount = '',
        /** YYYY-MM-DD, not in the future. */
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 2000)]
        public string $reason = '',
    ) {
    }
}
