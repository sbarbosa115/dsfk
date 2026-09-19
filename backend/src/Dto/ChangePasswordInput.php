<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordInput
{
    public function __construct(
        #[Assert\NotBlank]
        public ?string $currentPassword = null,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        #[Assert\NotEqualTo(propertyPath: 'currentPassword', message: 'La nueva contraseña debe ser diferente a la actual.')]
        public ?string $newPassword = null,
    ) {
    }
}
