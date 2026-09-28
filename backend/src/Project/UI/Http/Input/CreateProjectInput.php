<?php

declare(strict_types=1);

namespace App\Project\UI\Http\Input;

use App\Project\Domain\Model\ProjectStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateProjectInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $name = '',
        #[Assert\Length(max: 5000)]
        public ?string $description = null,
        /** Default: the currency in Settings. */
        #[Assert\Currency]
        public ?string $currency = null,
        public ?ProjectStatus $status = null,
        /** YYYY-MM-DD */
        #[Assert\Date]
        public ?string $plannedStart = null,
        /** YYYY-MM-DD */
        #[Assert\Date]
        public ?string $plannedEnd = null,
    ) {
    }
}
