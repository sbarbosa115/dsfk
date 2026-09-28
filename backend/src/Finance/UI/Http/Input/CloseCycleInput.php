<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class CloseCycleInput
{
    public function __construct(
        #[Assert\Length(max: 2000)]
        public ?string $note = null,
    ) {
    }
}
