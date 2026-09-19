<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SettingsInput
{
    /**
     * @param list<int>|null $budgetWarningPercents
     */
    public function __construct(
        #[Assert\Currency]
        public ?string $defaultCurrency = null,

        #[Assert\Regex(pattern: '/^\d{1,13}(\.\d{1,2})?$/', message: 'Debe ser un monto positivo.')]
        public ?string $teamLeadExpenseLimit = null,

        #[Assert\Range(min: 1, max: 100)]
        public ?int $pettyCashLowBalancePercent = null,

        #[Assert\Count(min: 1, max: 5)]
        #[Assert\All([new Assert\Type('int'), new Assert\Range(min: 1, max: 200)])]
        public ?array $budgetWarningPercents = null,
    ) {
    }
}
