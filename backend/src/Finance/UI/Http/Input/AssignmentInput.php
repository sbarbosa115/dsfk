<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Input;

use App\Finance\Domain\Model\LedgerAccount;
use App\Shared\UI\Http\Patterns;
use Symfony\Component\Validator\Constraints as Assert;

final class AssignmentInput
{
    public function __construct(
        /** STAGE or PETTY_CASH. */
        #[Assert\NotNull(message: 'Choose where the money goes.')]
        public ?LedgerAccount $destination = null,
        /** Required when the destination is STAGE. */
        public ?int $stageId = null,
        /** Major units. */
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::AMOUNT, message: 'It must be a positive amount.')]
        public string $amount = '',
        /** YYYY-MM-DD, not in the future. */
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date = '',
        #[Assert\Length(max: 2000)]
        public ?string $note = null,
    ) {
    }
}
