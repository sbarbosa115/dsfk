<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

/** Create (every field in group "create" required) or edit a user (only the fields sent change). */
final class UserInput
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public ?string $email = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Length(max: 150)]
        public ?string $fullName = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Length(min: 8, max: 4096)]
        public ?string $password = null,
        public ?bool $admin = null,
        public ?bool $superAdmin = null,
        public ?bool $active = null,
    ) {
    }
}
