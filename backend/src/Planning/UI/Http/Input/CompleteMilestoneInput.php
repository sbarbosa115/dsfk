<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class CompleteMilestoneInput
{
    public function __construct(
        /** YYYY-MM-DD, not in the future. */
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $completedAt = '',
        #[Assert\Length(max: 2000)]
        public ?string $notes = null,
    ) {
    }
}
