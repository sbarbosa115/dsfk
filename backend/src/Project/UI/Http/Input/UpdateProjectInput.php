<?php

declare(strict_types=1);

namespace App\Project\UI\Http\Input;

use App\Project\Domain\Model\ProjectStatus;
use Symfony\Component\Validator\Constraints as Assert;

/** Only the fields sent change; null clears a date, "" clears the description. The currency cannot change. */
final class UpdateProjectInput
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 150)]
        public ?string $name = null,
        #[Assert\Length(max: 5000)]
        public ?string $description = null,
        #[Assert\Currency]
        public ?string $currency = null,
        public ?ProjectStatus $status = null,
        #[Assert\Date]
        public ?string $plannedStart = null,
        #[Assert\Date]
        public ?string $plannedEnd = null,
    ) {
    }
}
