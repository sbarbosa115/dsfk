<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

/** Only the fields sent change; null clears a date. */
final class UpdateStageInput
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 150)]
        public ?string $name = null,
        #[Assert\Date]
        public ?string $plannedStart = null,
        #[Assert\Date]
        public ?string $plannedEnd = null,
    ) {
    }
}
