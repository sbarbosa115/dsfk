<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Input;

use App\Expense\Domain\Model\PayoutMethod;
use Symfony\Component\Validator\Constraints as Assert;

final class ReimbursementInput
{
    /**
     * @param list<int> $expenseIds
     */
    public function __construct(
        #[Assert\Count(min: 1, max: 100, minMessage: 'Selecciona al menos un gasto.')]
        #[Assert\All([new Assert\Type('int')])]
        public array $expenseIds = [],
        /** YYYY-MM-DD, not in the future. */
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date = '',
        #[Assert\NotNull(message: 'Elige el medio de pago.')]
        public ?PayoutMethod $method = null,
        #[Assert\Length(max: 100)]
        public ?string $reference = null,
    ) {
    }
}
