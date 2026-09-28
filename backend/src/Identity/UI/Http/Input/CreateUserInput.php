<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $fullName = '',
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        public string $password = '',
        public bool $admin = false,
        public bool $superAdmin = false,
    ) {
    }
}
