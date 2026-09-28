<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Input;

use App\Shared\UI\Http\Patterns;
use Symfony\Component\Validator\Constraints as Assert;

/** Only the fields sent change; null clears the planned date. */
final class UpdateMilestoneInput
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 200)]
        public ?string $name = null,
        #[Assert\Regex(pattern: Patterns::PERCENT, message: 'Porcentaje inválido (máximo 2 decimales).')]
        public ?string $weight = null,
        #[Assert\Date]
        public ?string $plannedDate = null,
    ) {
    }
}
