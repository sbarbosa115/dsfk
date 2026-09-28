<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

/** Edit a user: only the fields sent change. */
final class UpdateUserInput
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public ?string $email = null,
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 150)]
        public ?string $fullName = null,
        #[Assert\Length(min: 8, max: 4096)]
        public ?string $password = null,
        public ?bool $admin = null,
        public ?bool $superAdmin = null,
        public ?bool $active = null,
    ) {
    }
}
