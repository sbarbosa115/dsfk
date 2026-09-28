<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class ReasonInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 2000)]
        public string $reason = '',
    ) {
    }
}
