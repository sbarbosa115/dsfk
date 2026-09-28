<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordInput
{
    public function __construct(
        #[Assert\NotBlank]
        public string $currentPassword = '',
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        #[Assert\NotEqualTo(propertyPath: 'currentPassword', message: 'La nueva contraseña debe ser diferente a la actual.')]
        public string $newPassword = '',
    ) {
    }
}
