<?php

namespace App\Service;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

/**
 * Money is stored as integer minor units (BIGINT) and exchanged with the API as
 * decimal strings in major units ("1500000.00"). Floats are never involved.
 */
final class MoneyConverter
{
    /**
     * @throws \InvalidArgumentException when the value is not a valid amount for the currency
     */
    public static function toMinor(string $amount, string $currency): int
    {
        try {
            return Money::of($amount, $currency)->getMinorAmount()->toInt();
        } catch (MathException $e) {
            throw new \InvalidArgumentException(\sprintf('Invalid amount "%s" for %s.', $amount, $currency), previous: $e);
        }
    }

    public static function toMajor(int $minor, string $currency): string
    {
        return (string) Money::ofMinor($minor, $currency)->getAmount();
    }

    /** unit price (minor units) × quantity, rounded half-up to whole minor units. */
    public static function multiply(int $unitPriceMinor, string $quantity): int
    {
        return BigDecimal::of($unitPriceMinor)->multipliedBy($quantity)->toScale(0, RoundingMode::HalfUp)->toInt();
    }

    /** Human format for emails: "$ 1.500.000" (COP without decimals). */
    public static function format(int $minor, string $currency): string
    {
        $major = Money::ofMinor($minor, $currency)->getAmount();
        $decimals = 'COP' === $currency ? 0 : 2;
        $number = number_format((float) (string) $major->toScale($decimals, RoundingMode::HalfUp), $decimals, ',', '.');

        return 'COP' === $currency ? '$ '.$number : $currency.' '.$number;
    }
}
