<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class ReturnBudgetInput
{
    public function __construct(
        /** What the PM should change. */
        #[Assert\NotBlank]
        #[Assert\Length(max: 2000)]
        public string $comment = '',
    ) {
    }
}
