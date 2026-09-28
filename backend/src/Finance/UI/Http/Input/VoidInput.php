<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class VoidInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 2000)]
        public string $reason = '',
    ) {
    }
}
