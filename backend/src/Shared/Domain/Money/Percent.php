<?php

declare(strict_types=1);

namespace App\Shared\Domain\Money;

use App\Shared\Domain\Error\InvalidValue;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;

/** Percentages travel as decimal strings ("12.5") and are kept in basis points (1250 = 12.5 %). */
final class Percent
{
    /**
     * @throws InvalidValue when it has more than two decimals or is not a number
     */
    public static function toBasisPoints(string $percent): int
    {
        try {
            return BigDecimal::of($percent)->multipliedBy(100)->toScale(0)->toInt();
        } catch (MathException) {
            throw new InvalidValue('invalid_percent', ['percent' => $percent]);
        }
    }

    public static function fromBasisPoints(int $basisPoints): string
    {
        $value = BigDecimal::of($basisPoints)->dividedBy(100, 2, RoundingMode::Unnecessary);

        return rtrim(rtrim((string) $value, '0'), '.');
    }
}
