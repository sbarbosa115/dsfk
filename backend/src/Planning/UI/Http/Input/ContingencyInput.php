<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Input;

use App\Shared\UI\Http\Patterns;
use Symfony\Component\Validator\Constraints as Assert;

final class ContingencyInput
{
    public function __construct(
        /** Major units. */
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::AMOUNT, message: 'It must be a positive amount.')]
        public string $contingency = '',
    ) {
    }
}
