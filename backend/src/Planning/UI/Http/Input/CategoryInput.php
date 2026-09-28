<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class CategoryInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $name = '',
    ) {
    }
}
