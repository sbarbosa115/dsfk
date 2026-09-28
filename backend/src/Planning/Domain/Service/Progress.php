<?php

declare(strict_types=1);

namespace App\Planning\Domain\Service;

use App\Planning\Domain\Model\Stage;
use App\Shared\Domain\Money\MinorUnits;

/**
 * Weighted progress, in basis points. A stage weighs its share of the budget (contingency excluded); the
 * project's progress is Σ(stage progress × stage weight). While the budget is empty, a plain average.
 */
final class Progress
{
    /**
     * @param list<Stage> $stages
     */
    public static function ofProject(array $stages): int
    {
        if ([] === $stages) {
            return 0;
        }
        $total = self::budget($stages);
        if (0 === $total) {
            return intdiv(array_sum(array_map(static fn (Stage $s): int => $s->progress(), $stages)), \count($stages));
        }

        return intdiv(array_sum(array_map(static fn (Stage $s): int => $s->progress() * $s->budgetTotal(), $stages)), $total);
    }

    /**
     * @param list<Stage> $stages
     */
    public static function stageWeight(Stage $stage, array $stages): int
    {
        return MinorUnits::basisPoints($stage->budgetTotal(), self::budget($stages));
    }

    /**
     * @param list<Stage> $stages
     */
    public static function budget(array $stages): int
    {
        return array_sum(array_map(static fn (Stage $s): int => $s->budgetTotal(), $stages));
    }
}
