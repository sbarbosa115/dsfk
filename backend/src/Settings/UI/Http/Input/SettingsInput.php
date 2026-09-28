<?php

declare(strict_types=1);

namespace App\Settings\UI\Http\Input;

use App\Shared\UI\Http\Patterns;
use Symfony\Component\Validator\Constraints as Assert;

final class SettingsInput
{
    /**
     * @param list<int>|null $budgetWarningPercents
     */
    public function __construct(
        #[Assert\Currency]
        public ?string $defaultCurrency = null,
        #[Assert\Regex(pattern: Patterns::AMOUNT, message: 'It must be a positive amount.')]
        public ?string $teamLeadExpenseLimit = null,
        #[Assert\Range(min: 1, max: 100)]
        public ?int $pettyCashLowBalancePercent = null,
        #[Assert\Count(min: 1, max: 5)]
        #[Assert\All([new Assert\Type('int'), new Assert\Range(min: 1, max: 200)])]
        public ?array $budgetWarningPercents = null,
    ) {
    }
}
