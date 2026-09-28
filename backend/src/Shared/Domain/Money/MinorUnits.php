<?php

declare(strict_types=1);

namespace App\Shared\Domain\Money;

use App\Shared\Domain\Error\InvalidValue;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

/**
 * Money is stored as integer minor units (BIGINT) and exchanged with the API as decimal strings in major units
 * ("1500000.00"). Floats are never involved.
 */
final class MinorUnits
{
    /**
     * @throws InvalidValue when the value is not a valid amount for the currency
     */
    public static function fromMajor(string $amount, string $currency): int
    {
        try {
            return Money::of($amount, $currency)->getMinorAmount()->toInt();
        } catch (MathException $e) {
            throw new InvalidValue('invalid_amount', ['amount' => $amount]);
        }
    }

    public static function toMajor(int $minor, string $currency): string
    {
        return (string) Money::ofMinor($minor, $currency)->getAmount();
    }

    /** Unit price (minor units) × quantity, rounded half-up to whole minor units. */
    public static function multiply(int $unitPriceMinor, string $quantity): int
    {
        return BigDecimal::of($unitPriceMinor)->multipliedBy($quantity)->toScale(0, RoundingMode::HalfUp)->toInt();
    }

    /** Share of a whole in basis points (1 = 0.01 %), rounded half-up; 0 when the whole is 0. */
    public static function basisPoints(int $part, int $whole): int
    {
        if (0 === $whole) {
            return 0;
        }

        return BigDecimal::of($part)->multipliedBy(10000)->dividedBy($whole, 0, RoundingMode::HalfUp)->toInt();
    }

    /** Human format for emails: "$ 1.500.000" (COP without decimals), "USD 12,50" otherwise. */
    public static function format(int $minor, string $currency): string
    {
        $decimals = 'COP' === $currency ? 0 : 2;
        $major = Money::ofMinor($minor, $currency)->getAmount()->toScale($decimals, RoundingMode::HalfUp);
        [$whole, $fraction] = explode('.', $major->abs()->toScale(max($decimals, 1))->__toString()) + [1 => ''];
        $grouped = strrev(implode('.', str_split(strrev($whole), 3)));
        $number = ($major->isNegative() ? '-' : '').$grouped.($decimals > 0 ? ','.$fraction : '');

        return 'COP' === $currency ? '$ '.$number : $currency.' '.$number;
    }
}
