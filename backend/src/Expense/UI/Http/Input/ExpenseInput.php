<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Input;

use App\Expense\Domain\Model\PaidFrom;
use App\Shared\UI\Http\Patterns;
use Symfony\Component\Validator\Constraints as Assert;

final class ExpenseInput
{
    public function __construct(
        #[Assert\NotNull(message: 'Elige la etapa.')]
        public ?int $stageId = null,
        #[Assert\NotNull(message: 'Elige la categoría.')]
        public ?int $categoryId = null,
        /** YYYY-MM-DD, not in the future. */
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date = '',
        /** Major units. */
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::AMOUNT, message: 'Debe ser un monto positivo.')]
        public string $amount = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $description = '',
        #[Assert\Length(max: 150)]
        public ?string $supplier = null,
        #[Assert\Length(max: 60)]
        public ?string $invoiceNumber = null,
        /** STAGE or PETTY_CASH for the PM and Admins; Team Leads' expenses are OUT_OF_POCKET. Ignored on a correction. */
        public ?PaidFrom $paidFrom = null,
    ) {
    }
}
