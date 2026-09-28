<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Domain\Model\Category;
use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Error\NotFound;
use App\Shared\Domain\Money\MinorUnits;

/** What adding and changing a line share: its category (as a field error) and its price in minor units. */
final class LineCategory
{
    public static function of(PlanRepository $plans, int $categoryId): Category
    {
        try {
            return $plans->category($categoryId);
        } catch (NotFound) {
            throw InvalidValue::field('categoryId', 'La categoría no existe.');
        }
    }

    public static function price(string $amount, string $currency): int
    {
        try {
            return MinorUnits::fromMajor($amount, $currency);
        } catch (InvalidValue) {
            throw InvalidValue::field('unitPrice', 'Monto inválido para la moneda del proyecto.');
        }
    }
}
