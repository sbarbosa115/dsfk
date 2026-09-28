<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

use App\Expense\Application\Port\ExpensePlan;
use App\Expense\Application\Port\SpendingLimits;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Money\MinorUnits;

/** The stage and category must be the project's, and the amount valid in its currency (field errors otherwise). */
final readonly class CheckedDetails
{
    /**
     * @param array<int, array{name: string, completed: bool}> $stages
     * @param array<int, string>                               $categories
     */
    private function __construct(public ExpenseDetails $details, public int $amount, public array $stages, public array $categories)
    {
    }

    public static function of(ExpenseDetails $details, int $projectId, ExpensePlan $plan, SpendingLimits $limits): self
    {
        $stages = $plan->stages($projectId);
        $categories = $plan->categories($projectId);
        if (!isset($stages[$details->stageId])) {
            throw InvalidValue::field('stageId', 'Etapa inválida.');
        }
        if (!isset($categories[$details->categoryId])) {
            throw InvalidValue::field('categoryId', 'Categoría inválida.');
        }
        try {
            $amount = MinorUnits::fromMajor($details->amount, $limits->currency($projectId));
        } catch (InvalidValue) {
            throw InvalidValue::field('amount', 'Monto inválido para la moneda del proyecto.');
        }

        return new self($details, $amount, $stages, $categories);
    }
}
