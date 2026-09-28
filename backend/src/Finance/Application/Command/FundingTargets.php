<?php

declare(strict_types=1);

namespace App\Finance\Application\Command;

use App\Finance\Application\Port\FundedPlan;
use App\Finance\Application\Port\PlanStage;
use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Money\MinorUnits;

/**
 * Where money of an approved plan may go: its stages that are not completed and its categories. Wrong targets are
 * field errors on the path the request used.
 */
final readonly class FundingTargets
{
    /** @var array<int, PlanStage> */
    private array $stages;

    /** @var array<int, true> */
    private array $categories;

    /**
     * @throws Conflict budget_not_approved
     */
    public function __construct(FundedPlan $plan, int $projectId)
    {
        if (!$plan->isApproved($projectId)) {
            throw new Conflict('budget_not_approved');
        }
        $stages = [];
        foreach ($plan->stages($projectId) as $stage) {
            $stages[$stage->id] = $stage;
        }
        $this->stages = $stages;
        $this->categories = array_fill_keys(array_map(static fn ($c): int => $c->id, $plan->categories($projectId)), true);
    }

    public function openStage(?int $stageId, string $field): int
    {
        $stage = null === $stageId ? null : ($this->stages[$stageId] ?? null);
        if (null === $stage) {
            throw InvalidValue::field($field, 'Invalid stage.');
        }
        if ($stage->isCompleted()) {
            throw InvalidValue::field($field, 'The stage is already completed.');
        }

        return $stage->id;
    }

    public function category(?int $categoryId, string $field): ?int
    {
        if (null !== $categoryId && !isset($this->categories[$categoryId])) {
            throw InvalidValue::field($field, 'Invalid category.');
        }

        return $categoryId;
    }

    public static function minor(string $amount, string $currency, string $field): int
    {
        try {
            return MinorUnits::fromMajor($amount, $currency);
        } catch (InvalidValue) {
            throw InvalidValue::field($field, 'Invalid amount for the project\'s currency.');
        }
    }

    /** A field error raised by the domain ("amount"), moved under the part of the request it came from. */
    public static function prefixed(InvalidValue $error, string $path): InvalidValue
    {
        $violations = $error->extra['violations'] ?? null;
        if (!\is_array($violations)) {
            return $error;
        }
        $moved = [];
        foreach ($violations as $field => $messages) {
            $moved["$path.$field"] = $messages;
        }

        return new InvalidValue($error->errorCode, ['violations' => $moved] + $error->extra);
    }
}
